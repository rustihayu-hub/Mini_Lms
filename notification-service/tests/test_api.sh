#!/usr/bin/env bash
# Uji otomatis Notification Service.
# Pakai:  ./tests/test_api.sh [BASE_URL] [TOKEN]
# Contoh: ./tests/test_api.sh http://127.0.0.1:8101 eyJhbGciOi...   (lewat Gateway)
# Tanpa argumen: langsung ke service (8106) dengan token "abc123" (cocok untuk AUTH_MODE=format_only).

BASE="${1:-http://127.0.0.1:8106}"
TOKEN="${2:-abc123}"
TMP="$(mktemp)"
PASS=0; FAIL=0

check() { # nama, status_diharapkan, status_aktual
  if [ "$2" = "$3" ]; then PASS=$((PASS+1)); printf 'LULUS  %-42s diharapkan=%s aktual=%s\n' "$1" "$2" "$3"
  else FAIL=$((FAIL+1)); printf 'GAGAL  %-42s diharapkan=%s aktual=%s\n' "$1" "$2" "$3"; cat "$TMP"; echo; fi
}
call() { curl -s -o "$TMP" -w '%{http_code}' --max-time 15 "$@"; }
JSON='Content-Type: application/json'
AUTH="Authorization: Bearer $TOKEN"

check "Health"                         200 "$(call "$BASE/health")"
check "POST sukses"                    201 "$(call -X POST "$BASE/api/notifications" -H "$AUTH" -H "$JSON" -d '{"user_id":"user-test","message":"Submission berhasil diterima","type":"submission","submission_id":"sub-001"}')"
NEW_ID="$(grep -o '"id": *"notif-[0-9]*"' "$TMP" | head -1 | sed 's/.*"\(notif-[0-9]*\)"/\1/')"
check "POST body kosong"               400 "$(call -X POST "$BASE/api/notifications" -H "$AUTH" -H "$JSON" -d '{}')"
check "POST JSON rusak"                400 "$(call -X POST "$BASE/api/notifications" -H "$AUTH" -H "$JSON" -d '{rusak')"
check "POST bukan JSON"                415 "$(call -X POST "$BASE/api/notifications" -H "$AUTH" -H 'Content-Type: text/plain' -d 'halo')"
check "POST tanpa token"               401 "$(call -X POST "$BASE/api/notifications" -H "$JSON" -d '{"user_id":"u","message":"m"}')"
check "GET daftar user"                200 "$(call "$BASE/api/notifications?user_id=user-test" -H "$AUTH")"
check "GET tanpa user_id"              400 "$(call "$BASE/api/notifications" -H "$AUTH")"
check "GET tanpa token"                401 "$(call "$BASE/api/notifications?user_id=user-test")"
check "PATCH tandai dibaca ($NEW_ID)"  200 "$(call -X PATCH "$BASE/api/notifications/$NEW_ID/read" -H "$AUTH")"
check "PATCH id tidak ada"             404 "$(call -X PATCH "$BASE/api/notifications/notif-999/read" -H "$AUTH")"
check "PATCH tanpa token"              401 "$(call -X PATCH "$BASE/api/notifications/$NEW_ID/read")"

rm -f "$TMP"
echo; echo "Ringkasan: $PASS lulus, $FAIL gagal"
[ "$FAIL" -eq 0 ]
