# Uji otomatis Notification Service (PowerShell, memakai curl.exe bawaan Windows).
# Pakai:  .\tests\test_api.ps1 -BaseUrl http://127.0.0.1:8101 -Token "<token>"
# Tanpa parameter: langsung ke service (8106) dengan token "abc123" (cocok untuk AUTH_MODE=format_only).
param(
    [string]$BaseUrl = "http://127.0.0.1:8106",
    [string]$Token   = "abc123"
)

$script:Pass = 0
$script:Fail = 0
$tmp  = New-TemporaryFile
$body = New-TemporaryFile

function Invoke-Call {
    param([string[]]$CurlArgs)
    $code = & curl.exe -s -o $tmp.FullName -w "%{http_code}" --max-time 15 @CurlArgs
    return "$code"
}

function Test-Case {
    param([string]$Name, [string]$Expected, [string]$Actual)
    if ($Expected -eq $Actual) {
        $script:Pass++
        Write-Host ("LULUS  {0,-42} diharapkan={1} aktual={2}" -f $Name, $Expected, $Actual) -ForegroundColor Green
    } else {
        $script:Fail++
        Write-Host ("GAGAL  {0,-42} diharapkan={1} aktual={2}" -f $Name, $Expected, $Actual) -ForegroundColor Red
        Get-Content $tmp.FullName
    }
}

function Set-Body([string]$json) {
    # Tulis tanpa BOM agar curl mengirim JSON murni.
    [System.IO.File]::WriteAllText($body.FullName, $json, (New-Object System.Text.UTF8Encoding($false)))
}

$auth = "Authorization: Bearer $Token"
$json = "Content-Type: application/json"

Test-Case "Health" "200" (Invoke-Call @("$BaseUrl/health"))

Set-Body '{"user_id":"user-test","message":"Submission berhasil diterima","type":"submission","submission_id":"sub-001"}'
Test-Case "POST sukses" "201" (Invoke-Call @("-X","POST","$BaseUrl/api/notifications","-H",$auth,"-H",$json,"--data-binary","@$($body.FullName)"))
$newId = ([regex]::Match((Get-Content $tmp.FullName -Raw), '"id":\s*"(notif-\d+)"')).Groups[1].Value

Set-Body '{}'
Test-Case "POST body kosong" "400" (Invoke-Call @("-X","POST","$BaseUrl/api/notifications","-H",$auth,"-H",$json,"--data-binary","@$($body.FullName)"))

Set-Body '{rusak'
Test-Case "POST JSON rusak" "400" (Invoke-Call @("-X","POST","$BaseUrl/api/notifications","-H",$auth,"-H",$json,"--data-binary","@$($body.FullName)"))

Set-Body 'halo'
Test-Case "POST bukan JSON" "415" (Invoke-Call @("-X","POST","$BaseUrl/api/notifications","-H",$auth,"-H","Content-Type: text/plain","--data-binary","@$($body.FullName)"))

Set-Body '{"user_id":"u","message":"m"}'
Test-Case "POST tanpa token" "401" (Invoke-Call @("-X","POST","$BaseUrl/api/notifications","-H",$json,"--data-binary","@$($body.FullName)"))

Test-Case "GET daftar user"   "200" (Invoke-Call @("$BaseUrl/api/notifications?user_id=user-test","-H",$auth))
Test-Case "GET tanpa user_id" "400" (Invoke-Call @("$BaseUrl/api/notifications","-H",$auth))
Test-Case "GET tanpa token"   "401" (Invoke-Call @("$BaseUrl/api/notifications?user_id=user-test"))

Test-Case "PATCH tandai dibaca ($newId)" "200" (Invoke-Call @("-X","PATCH","$BaseUrl/api/notifications/$newId/read","-H",$auth))
Test-Case "PATCH id tidak ada"           "404" (Invoke-Call @("-X","PATCH","$BaseUrl/api/notifications/notif-999/read","-H",$auth))
Test-Case "PATCH tanpa token"            "401" (Invoke-Call @("-X","PATCH","$BaseUrl/api/notifications/$newId/read"))

Remove-Item $tmp.FullName, $body.FullName -ErrorAction SilentlyContinue
Write-Host ""
Write-Host "Ringkasan: $($script:Pass) lulus, $($script:Fail) gagal"
if ($script:Fail -gt 0) { exit 1 }
