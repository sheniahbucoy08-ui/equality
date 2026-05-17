# Compile SASS sources to CSS using an ephemeral Docker container.
# No local Node/sass install required.
#
# Usage from project root:
#   .\scripts\compile-sass.ps1            # one-shot compile
#   .\scripts\compile-sass.ps1 -Watch     # watch mode: auto-recompile on change
#
# Output goes to css/main.css and css/admin.css. Because the live
# docker-compose.override.yml bind-mounts ./ into the web container,
# the changes are visible immediately at http://localhost:8000/ — just
# Ctrl+F5 your browser to bypass the cache.

param(
    [switch]$Watch
)

$ErrorActionPreference = 'Stop'

$mainArgs   = "sass/main.scss:css/main.css"
$adminArgs  = "sass/_admin.scss:css/admin.css"
$commonOpts = "--no-source-map --style=expanded"

if ($Watch) {
    Write-Host "Starting SASS watcher (Ctrl+C to stop)..."
    $cmd = "npm install -g sass --silent --no-fund --no-audit && sass --watch $commonOpts $mainArgs $adminArgs"
} else {
    Write-Host "Compiling SASS once..."
    $cmd = "npm install -g sass --silent --no-fund --no-audit && sass $commonOpts $mainArgs $adminArgs"
}

docker run --rm -it -v "${PWD}:/src" -w /src node:20-alpine sh -c $cmd
