# Create a timestamp for the zip file name
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$projectName = "ndc-lookup"
$zipName = "${projectName}_${timestamp}.zip"

Write-Host "Creating zip file: $zipName"

# Create a temporary directory
$tempDir = "temp_for_zip"
Write-Host "Creating temporary directory..."
New-Item -ItemType Directory -Force -Path $tempDir | Out-Null

# Copy files to temporary directory, excluding unnecessary files
Write-Host "Copying project files..."
$excludeDirs = @(
    '.git',
    'node_modules',
    'vendor',
    '.vercel',
    'storage/logs',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'api'  # Vercel API directory
)

$excludeFiles = @(
    'vercel.json',
    'vercel-build.sh',
    'render.yaml',
    'render-build.sh',
    '.vercelignore',
    'create-zip.ps1'
)

Get-ChildItem -Path . -Exclude $excludeDirs | 
    Where-Object { $_.Name -notin $excludeFiles } |
    Copy-Item -Destination $tempDir -Recurse -Force

# Create required directories in storage
Write-Host "Creating required storage directories..."
$storageDirs = @(
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs'
)

foreach ($dir in $storageDirs) {
    New-Item -ItemType Directory -Force -Path "$tempDir/$dir" | Out-Null
    New-Item -ItemType File -Force -Path "$tempDir/$dir/.gitkeep" | Out-Null
}

# Create a basic .env file if it doesn't exist
if (-not (Test-Path "$tempDir/.env")) {
    Write-Host "Creating basic .env file..."
    Copy-Item ".env.example" -Destination "$tempDir/.env" -Force
}

# Create the zip file
Write-Host "Creating zip archive..."
Compress-Archive -Path "$tempDir/*" -DestinationPath $zipName -Force

# Clean up
Write-Host "Cleaning up temporary files..."
Remove-Item -Recurse -Force $tempDir

Write-Host "Done! Your zip file is ready: $zipName"
Write-Host "`nIMPORTANT: The recipient will need to:"
Write-Host "1. Extract the zip file"
Write-Host "2. Run 'composer install' to install PHP dependencies"
Write-Host "3. Run 'npm install' to install Node.js dependencies"
Write-Host "4. Copy .env.example to .env and configure the database settings"
Write-Host "5. Run 'php artisan key:generate' to generate an application key"
Write-Host "6. Run 'php artisan migrate' to set up the database"
Write-Host "7. Run 'npm run build' to build the frontend assets"
Write-Host "8. Configure their web server to point to the public directory" 