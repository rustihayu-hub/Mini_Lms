$BaseUrl = if ($env:ASSIGNMENT_BASE_URL) {
    $env:ASSIGNMENT_BASE_URL
}
else {
    "http://127.0.0.1:8104"
}

function Invoke-Api {
    param(
        [string]$Method,
        [string]$Url,
        [object]$Body = $null
    )

    $params = @{
        Method      = $Method
        Uri         = $Url
        ErrorAction = "Stop"
    }

    if ($null -ne $Body) {
        $params.ContentType = "application/json"
        $params.Body = ($Body | ConvertTo-Json -Depth 10)
    }

    return Invoke-RestMethod @params
}

Write-Host "`n=== Assignment Service Test ===" -ForegroundColor Cyan

# 1. Health
$health = Invoke-Api -Method GET -Url "$BaseUrl/health"
if ($health.data.status -eq "healthy") {
    Write-Host "[PASS] Health check" -ForegroundColor Green
}
else {
    throw "Health check gagal"
}

# 2. GET list
$list = Invoke-Api -Method GET -Url "$BaseUrl/api/assignments?course_id=course-001"
if ($list.success) {
    Write-Host "[PASS] GET assignment list" -ForegroundColor Green
}
else {
    throw "GET list gagal"
}

# 3. POST create
$created = Invoke-Api -Method POST -Url "$BaseUrl/api/assignments" -Body @{
    course_id   = "course-001"
    title       = "Automated Test Assignment"
    description = "Pengujian API otomatis"
}

if ($created.success -and $created.data.id) {
    $CreatedId = $created.data.id
    Write-Host "[PASS] POST create ($CreatedId)" -ForegroundColor Green
}
else {
    throw "POST create gagal"
}

# 4. GET detail
$detail = Invoke-Api -Method GET -Url "$BaseUrl/api/assignments/$CreatedId"
if ($detail.success) {
    Write-Host "[PASS] GET detail" -ForegroundColor Green
}
else {
    throw "GET detail gagal"
}

# 5. DELETE
$deleted = Invoke-Api -Method DELETE -Url "$BaseUrl/api/assignments/$CreatedId"
if ($deleted.success -and $deleted.data.deleted) {
    Write-Host "[PASS] DELETE assignment" -ForegroundColor Green
}
else {
    throw "DELETE gagal"
}

# 6. Verify deletion
try {
    Invoke-Api -Method GET -Url "$BaseUrl/api/assignments/$CreatedId"
    Write-Host "[FAIL] Assignment masih ditemukan" -ForegroundColor Red
    throw "Assignment belum terhapus"
}
catch {
    if ($_.Exception.Message -match "Assignment belum terhapus") {
        throw
    }
    Write-Host "[PASS] Verify deletion (404)" -ForegroundColor Green
}

Write-Host "`nSemua pengujian berhasil." -ForegroundColor Green