$ErrorActionPreference = "Stop"

$ProjectRoot = (Resolve-Path (Join-Path $PSScriptRoot ".."))
Set-Location $ProjectRoot

Write-Host "[1/5] Checking docker..."
if (-not (Get-Command docker -ErrorAction SilentlyContinue)) {
  throw "docker not found. Please install Docker Desktop / Docker Engine first."
}

# docker daemon
try { docker info | Out-Null } catch { throw "Docker daemon not running or permission denied." }

$composeCmd = $null
try { docker compose version | Out-Null; $composeCmd = "docker compose" } catch {}
if (-not $composeCmd) {
  if (Get-Command docker-compose -ErrorAction SilentlyContinue) { $composeCmd = "docker-compose" }
}
if (-not $composeCmd) {
  throw "docker compose / docker-compose not found. Please install docker-compose."
}

Write-Host "[2/5] Preparing .env ..."
if (-not (Test-Path ".env")) {
  if (Test-Path ".env.example") {
    Copy-Item ".env.example" ".env" -ErrorAction Stop
    Write-Host "Created .env from .env.example. Please edit .env then re-run." -ForegroundColor Yellow
    exit 1
  } else {
    throw ".env.example is missing."
  }
}

function Get-EnvValue([string]$Key) {
  $line = (Get-Content .env | Where-Object { $_ -match "^$Key=" } | Select-Object -Last 1)
  if (-not $line) { return $null }
  return $line.Substring($Key.Length + 1)
}

function Require-Env([string]$Key) {
  $v = Get-EnvValue $Key
  if ([string]::IsNullOrWhiteSpace($v)) { throw "Missing or empty $Key in .env" }
}

Require-Env "DB_HOST"
Require-Env "DB_USER"
Require-Env "DB_NAME"

$buildFlag = ""
if ($env:BUILD -eq "1") { $buildFlag = "--build" }

Write-Host "[3/5] Starting containers..."
Invoke-Expression "$composeCmd up -d $buildFlag"

Write-Host "[4/5] Status"
Invoke-Expression "$composeCmd ps"

$port = Get-EnvValue "APP_PORT"
if ([string]::IsNullOrWhiteSpace($port)) { $port = "8080" }

Write-Host "[5/5] Done."
Write-Host "Open: http://127.0.0.1:$port/"
Write-Host "First install (if needed): http://127.0.0.1:$port/install/"
