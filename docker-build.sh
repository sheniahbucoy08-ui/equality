#!/bin/bash
# EqualVoice Docker Build Script

set -e

echo "╔════════════════════════════════════════════╗"
echo "║     EqualVoice Docker Build Script       ║"
echo "╚════════════════════════════════════════════╝"
echo ""

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m'

print_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
print_success() { echo -e "${GREEN}✓ $1${NC}"; }
print_error() { echo -e "${RED}✗ $1${NC}"; }

# Get build parameters
IMAGE_NAME="${1:-equalvoice}"
IMAGE_TAG="${2:-latest}"
REGISTRY="${3:-docker.io}"

print_info "Building Docker image..."
print_info "Registry: $REGISTRY"
print_info "Image: $IMAGE_NAME"
print_info "Tag: $IMAGE_TAG"
echo ""

# Build the image
if docker build \
    --build-arg BUILD_DATE="$(date -u +'%Y-%m-%dT%H:%M:%SZ')" \
    --build-arg VCS_REF="$(git rev-parse --short HEAD 2>/dev/null || echo 'unknown')" \
    --build-arg BUILD_VERSION="$(git describe --tags --always 2>/dev/null || echo 'unknown')" \
    -t "${REGISTRY}/${IMAGE_NAME}:${IMAGE_TAG}" \
    -t "${REGISTRY}/${IMAGE_NAME}:latest" \
    .; then
    print_success "Image built successfully"
    echo ""
    print_info "Image details:"
    docker inspect "${REGISTRY}/${IMAGE_NAME}:${IMAGE_TAG}" | grep -A 20 '"Config"'
    echo ""
    print_info "To push the image to registry:"
    echo "  docker push ${REGISTRY}/${IMAGE_NAME}:${IMAGE_TAG}"
    echo "  docker push ${REGISTRY}/${IMAGE_NAME}:latest"
else
    print_error "Build failed"
    exit 1
fi
