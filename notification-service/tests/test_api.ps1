# Uji otomatis Notification Service (PowerShell, memakai curl.exe bawaan Windows).
# Pakai: .\tests\test_api.ps1 -BaseUrl http://127.0.0.1:8106
param(
    [string]$BaseUrl = "http://127.0.0.1:8106"
)

$script:Pass = 0
$script:Fail = 0
$tmp  = New-TemporaryFile
$body = New-TemporaryFile
$dataFile = Join-Path $PSScriptRoot '..\data\notifications.json'
$originalData = [System.IO.File]::ReadAllBytes((Resolve-Path $dataFile))

function Invoke-Call {
    param([string[]]$CurlArgs)
    $code = & curl.exe -s -o $tmp.FullName -w "%{http_code}" --max-time 15 @CurlArgs
    return "$code"
}

function Test-Case {
    param([string]$Name, [string]$Expected, [string]$Actual)
    $envelopeValid = $false
    try {
        $payload = Get-Content $tmp.FullName -Raw | ConvertFrom-Json -ErrorAction Stop
        $hasRequestId = $payload.meta -and -not [string]::IsNullOrWhiteSpace([string]$payload.meta.request_id)
        if ([int]$Expected -lt 400) {
            $envelopeValid = ($payload.success -eq $true) -and
                ($payload.PSObject.Properties.Name -contains 'data') -and $hasRequestId
        } else {
            $envelopeValid = ($payload.success -eq $false) -and
                $payload.error -and $payload.error.code -and $payload.error.message -and $hasRequestId
        }
    } catch {
        $envelopeValid = $false
    }

    if (($Expected -eq $Actual) -and $envelopeValid) {
        $script:Pass++
        Write-Host ("LULUS  {0,-42} diharapkan={1} aktual={2}" -f $Name, $Expected, $Actual) -ForegroundColor Green
    } else {
        $script:Fail++
        $reason = if ($Expected -ne $Actual) { "diharapkan=$Expected aktual=$Actual" } else { 'envelope response tidak sesuai kontrak' }
        Write-Host ("GAGAL  {0,-42} {1}" -f $Name, $reason) -ForegroundColor Red
        Get-Content $tmp.FullName
    }
}

function Set-Body([string]$json) {
    # Tulis tanpa BOM agar curl mengirim JSON murni.
    [System.IO.File]::WriteAllText($body.FullName, $json, (New-Object System.Text.UTF8Encoding($false)))
}

$json = "Content-Type: application/json"

try {
Test-Case "Health" "200" (Invoke-Call @("$BaseUrl/health"))
Test-Case "GET tanpa token" "200" (Invoke-Call @("$BaseUrl/api/notifications?user_id=1"))
Test-Case "GET Authorization diabaikan" "200" (Invoke-Call @("$BaseUrl/api/notifications?user_id=1","-H","Authorization: Basic ignored"))

Set-Body '{"user_id":"user-test","message":"Submission berhasil diterima","type":"submission","submission_id":"sub-001"}'
Test-Case "POST tanpa token" "201" (Invoke-Call @("-X","POST","$BaseUrl/api/notifications","-H",$json,"--data-binary","@$($body.FullName)"))
$newId = ([regex]::Match((Get-Content $tmp.FullName -Raw), '"id":\s*"(notif-\d+)"')).Groups[1].Value
if (-not $newId) {
    $script:Fail++
    Write-Host "GAGAL  POST mengembalikan ID notifikasi" -ForegroundColor Red
}

Set-Body '{}'
Test-Case "POST body kosong" "400" (Invoke-Call @("-X","POST","$BaseUrl/api/notifications","-H",$json,"--data-binary","@$($body.FullName)"))

Set-Body '{rusak'
Test-Case "POST JSON rusak" "400" (Invoke-Call @("-X","POST","$BaseUrl/api/notifications","-H",$json,"--data-binary","@$($body.FullName)"))

Set-Body 'halo'
Test-Case "POST bukan JSON" "415" (Invoke-Call @("-X","POST","$BaseUrl/api/notifications","-H","Content-Type: text/plain","--data-binary","@$($body.FullName)"))

Test-Case "GET daftar user"   "200" (Invoke-Call @("$BaseUrl/api/notifications?user_id=user-test"))
Test-Case "GET tanpa user_id" "400" (Invoke-Call @("$BaseUrl/api/notifications"))

if ($newId) {
    Test-Case "PATCH tanpa token ($newId)" "200" (Invoke-Call @("-X","PATCH","$BaseUrl/api/notifications/$newId/read"))
} else {
    $script:Fail++
    Write-Host "GAGAL  PATCH memakai ID dari POST yang berhasil" -ForegroundColor Red
}
Test-Case "PATCH id tidak ada"           "404" (Invoke-Call @("-X","PATCH","$BaseUrl/api/notifications/notif-999/read"))

Write-Host ""
Write-Host "Ringkasan: $($script:Pass) lulus, $($script:Fail) gagal"
} finally {
    [System.IO.File]::WriteAllBytes((Resolve-Path $dataFile), $originalData)
    Remove-Item $tmp.FullName, $body.FullName -ErrorAction SilentlyContinue
}
if ($script:Fail -gt 0) { exit 1 }
