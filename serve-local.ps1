$phpCommand = Get-Command php.exe -ErrorAction SilentlyContinue
$phpPath = if ($phpCommand) { $phpCommand.Source } elseif (Test-Path 'C:\xampp\php\php.exe') { 'C:\xampp\php\php.exe' } else { $null }

if (-not $phpPath) {
    throw 'PHP was not found. Install PHP or XAMPP, then run this script again.'
}

$env:APP_COOKIE_SECURE = '0'
Write-Host 'Starting the PHP development server at http://127.0.0.1:8000/'
Write-Host 'Configure DB_* environment variables and start MySQL before creating accounts.'
& $phpPath -S 127.0.0.1:8000 -t $PSScriptRoot
