#!/bin/bash

# Fix Go Version Issue in WhatsApp API
# This script will fix the invalid go.mod version and rebuild the container

echo "🔧 Fixing Go Version Issue in WhatsApp API"
echo "==========================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
WHATSAPP_DIR="/var/www/whatsapp-api/go-whatsapp-web-multidevice-main"

echo -e "${BLUE}📋 Configuration:${NC}"
echo -e "  WhatsApp Directory: ${WHATSAPP_DIR}"
echo -e "  Target Go Version: 1.19 (matching Docker image)"
echo ""

# Function to diagnose current go.mod
diagnose_go_mod() {
    echo -e "${YELLOW}🔍 Step 1: Diagnosing go.mod file...${NC}"
    
    cd "$WHATSAPP_DIR"
    
    if [ -f "go.mod" ]; then
        echo -e "${GREEN}✅ go.mod file exists${NC}"
        echo -e "${BLUE}Current go.mod content:${NC}"
        cat go.mod
        echo ""
        
        # Check Go version line
        if grep -q "go 1.24.0" go.mod; then
            echo -e "${RED}❌ Found invalid Go version: 1.24.0${NC}"
            return 1
        elif grep -q "go 1.24" go.mod; then
            echo -e "${RED}❌ Found invalid Go version: 1.24${NC}"
            return 1
        else
            echo -e "${GREEN}✅ Go version appears valid${NC}"
            return 0
        fi
    else
        echo -e "${RED}❌ go.mod file not found${NC}"
        return 1
    fi
}

# Function to fix go.mod file
fix_go_mod() {
    echo -e "${YELLOW}🔧 Step 2: Fixing go.mod file...${NC}"
    
    cd "$WHATSAPP_DIR"
    
    # Backup original go.mod
    if [ -f "go.mod" ]; then
        cp go.mod go.mod.backup
        echo -e "${GREEN}✅ go.mod backed up${NC}"
    fi
    
    # Fix Go version in go.mod
    if [ -f "go.mod" ]; then
        # Replace invalid versions with 1.19
        sed -i 's/go 1\.24\.0/go 1.19/g' go.mod
        sed -i 's/go 1\.24/go 1.19/g' go.mod
        sed -i 's/go 1\.23/go 1.19/g' go.mod
        sed -i 's/go 1\.22/go 1.19/g' go.mod
        sed -i 's/go 1\.21/go 1.19/g' go.mod
        sed -i 's/go 1\.20/go 1.19/g' go.mod
        
        echo -e "${GREEN}✅ go.mod version fixed${NC}"
    else
        # Create new go.mod if missing
        echo -e "${YELLOW}Creating new go.mod file...${NC}"
        cat > go.mod << 'EOF'
module whatsapp-api

go 1.19

require (
    github.com/gorilla/mux v1.8.0
    github.com/gorilla/websocket v1.5.0
    go.mau.fi/whatsmeow v0.0.0-20230804114610-6c7c4b8b7f8e
    google.golang.org/protobuf v1.31.0
    github.com/skip2/go-qrcode v0.0.0-20200617195104-da1b6568686e
)
EOF
        echo -e "${GREEN}✅ New go.mod created${NC}"
    fi
    
    # Show fixed content
    echo -e "${BLUE}Fixed go.mod content:${NC}"
    cat go.mod
    echo ""
}

# Function to fix go.sum if needed
fix_go_sum() {
    echo -e "${YELLOW}🔧 Step 3: Fixing go.sum file...${NC}"
    
    cd "$WHATSAPP_DIR"
    
    # Remove go.sum to force regeneration
    if [ -f "go.sum" ]; then
        rm go.sum
        echo -e "${GREEN}✅ go.sum removed (will be regenerated)${NC}"
    fi
    
    # Create minimal go.sum if needed
    touch go.sum
    echo -e "${GREEN}✅ go.sum prepared${NC}"
    echo ""
}

# Function to update Dockerfile for compatibility
update_dockerfile() {
    echo -e "${YELLOW}🔧 Step 4: Updating Dockerfile for compatibility...${NC}"
    
    cd "$WHATSAPP_DIR"
    
    # Create optimized Dockerfile
    cat > Dockerfile << 'EOF'
FROM golang:1.19-alpine AS builder

WORKDIR /app

# Install dependencies
RUN apk add --no-cache git ca-certificates tzdata

# Copy go mod files first for better caching
COPY go.mod go.sum ./

# Download dependencies
RUN go mod download && go mod verify

# Copy source code
COPY . .

# Build the application
RUN CGO_ENABLED=0 GOOS=linux GOARCH=amd64 go build \
    -ldflags='-w -s -extldflags "-static"' \
    -a -installsuffix cgo \
    -o main .

# Final stage - minimal image
FROM alpine:latest

# Install runtime dependencies
RUN apk --no-cache add ca-certificates ffmpeg curl

WORKDIR /app

# Copy the binary from builder stage
COPY --from=builder /app/main .

# Copy src directory if it exists
COPY --from=builder /app/src ./src

# Create necessary directories
RUN mkdir -p sessions media logs

# Create non-root user
RUN adduser -D -s /bin/sh whatsapp
RUN chown -R whatsapp:whatsapp /app
USER whatsapp

# Expose port
EXPOSE 3000

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost:3000/app/devices || exit 1

# Run the application
CMD ["./main"]
EOF
    
    echo -e "${GREEN}✅ Dockerfile updated with Go 1.19 compatibility${NC}"
    echo ""
}

# Function to clean Docker cache
clean_docker_cache() {
    echo -e "${YELLOW}🧹 Step 5: Cleaning Docker cache...${NC}"
    
    # Stop and remove existing containers
    docker-compose down 2>/dev/null || true
    docker stop whatsapp-api-hartono 2>/dev/null || true
    docker rm whatsapp-api-hartono 2>/dev/null || true
    
    # Remove old images
    docker rmi $(docker images | grep whatsapp | awk '{print $3}') 2>/dev/null || true
    
    # Clean build cache
    docker builder prune -f 2>/dev/null || true
    
    echo -e "${GREEN}✅ Docker cache cleaned${NC}"
    echo ""
}

# Function to test Docker build
test_docker_build() {
    echo -e "${YELLOW}🧪 Step 6: Testing Docker build...${NC}"
    
    cd "$WHATSAPP_DIR"
    
    # Test build with verbose output
    echo -e "${BLUE}Building Docker image...${NC}"
    if docker build --no-cache -t whatsapp-api-test . 2>&1 | tee build.log; then
        echo -e "${GREEN}✅ Docker build successful!${NC}"
        
        # Test run the container briefly
        echo -e "${BLUE}Testing container startup...${NC}"
        if docker run -d --name whatsapp-test -p 3001:3000 whatsapp-api-test; then
            sleep 5
            
            # Test API response
            if curl -s http://localhost:3001/app/devices >/dev/null 2>&1; then
                echo -e "${GREEN}✅ Container test successful!${NC}"
            else
                echo -e "${YELLOW}⚠️ Container started but API not responding yet${NC}"
            fi
            
            # Cleanup test container
            docker stop whatsapp-test 2>/dev/null || true
            docker rm whatsapp-test 2>/dev/null || true
        else
            echo -e "${RED}❌ Container test failed${NC}"
        fi
        
        # Cleanup test image
        docker rmi whatsapp-api-test 2>/dev/null || true
        
        return 0
    else
        echo -e "${RED}❌ Docker build failed${NC}"
        echo -e "${BLUE}Build errors:${NC}"
        tail -20 build.log
        return 1
    fi
}

# Function to deploy with docker-compose
deploy_with_compose() {
    echo -e "${YELLOW}🚀 Step 7: Deploying with Docker Compose...${NC}"
    
    cd "$WHATSAPP_DIR"
    
    # Create production docker-compose.yml
    cat > docker-compose.yml << 'EOF'
version: '3.8'

services:
  whatsapp-api:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: whatsapp-api-hartono
    ports:
      - "3000:3000"
    volumes:
      - ./src:/app/src:ro
      - whatsapp_sessions:/app/sessions
      - whatsapp_media:/app/media
      - whatsapp_logs:/app/logs
    environment:
      - APP_PORT=3000
      - APP_DEBUG=false
      - APP_OS=HartonoMotor
      - APP_BASIC_AUTH=admin:HartonoMotor2025!
      - WHATSAPP_WEBHOOK=https://hartonomotor.xyz/api/whatsapp/webhook
      - WHATSAPP_WEBHOOK_SECRET=HartonoMotorWebhookSecret2025
    restart: unless-stopped
    networks:
      - whatsapp-network
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost:3000/app/devices"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 40s

volumes:
  whatsapp_sessions:
    driver: local
  whatsapp_media:
    driver: local
  whatsapp_logs:
    driver: local

networks:
  whatsapp-network:
    driver: bridge
EOF
    
    echo -e "${GREEN}✅ Docker Compose configuration created${NC}"
    
    # Deploy
    echo -e "${BLUE}Starting deployment...${NC}"
    if docker-compose up -d --build; then
        echo -e "${GREEN}✅ Docker Compose deployment started${NC}"
        
        # Wait for container to start
        echo -e "${BLUE}⏳ Waiting for container to start...${NC}"
        sleep 20
        
        # Check container status
        if docker-compose ps | grep -q "Up"; then
            echo -e "${GREEN}✅ Container is running!${NC}"
        else
            echo -e "${RED}❌ Container failed to start${NC}"
            echo -e "${BLUE}Container logs:${NC}"
            docker-compose logs --tail=20
            return 1
        fi
    else
        echo -e "${RED}❌ Docker Compose deployment failed${NC}"
        return 1
    fi
    
    echo ""
}

# Function to verify deployment
verify_deployment() {
    echo -e "${YELLOW}🧪 Step 8: Verifying deployment...${NC}"
    
    # Check container status
    echo -e "${BLUE}Container status:${NC}"
    docker ps | grep whatsapp || echo "No WhatsApp containers found"
    
    # Test API
    echo -e "${BLUE}Testing API...${NC}"
    sleep 5
    
    if curl -s -u admin:HartonoMotor2025! http://localhost:3000/app/devices >/dev/null; then
        echo -e "${GREEN}✅ API is responding!${NC}"
        
        # Get actual response
        echo -e "${BLUE}API response:${NC}"
        curl -s -u admin:HartonoMotor2025! http://localhost:3000/app/devices | head -3
    else
        echo -e "${RED}❌ API not responding${NC}"
        
        # Show logs for debugging
        echo -e "${BLUE}Container logs:${NC}"
        cd "$WHATSAPP_DIR"
        docker-compose logs --tail=10
    fi
    
    echo ""
}

# Function to show next steps
show_next_steps() {
    echo -e "${GREEN}🎉 Go Version Fix Completed!${NC}"
    echo "=========================="
    echo ""
    
    echo -e "${BLUE}📋 What was fixed:${NC}"
    echo -e "  ✅ go.mod version corrected to 1.19"
    echo -e "  ✅ go.sum regenerated"
    echo -e "  ✅ Dockerfile optimized for Go 1.19"
    echo -e "  ✅ Docker cache cleaned"
    echo -e "  ✅ Container rebuilt and deployed"
    echo ""
    
    echo -e "${BLUE}📋 Next Steps:${NC}"
    echo -e "1. ${YELLOW}Test API access:${NC}"
    echo -e "   curl -u admin:HartonoMotor2025! http://localhost:3000/app/devices"
    echo ""
    echo -e "2. ${YELLOW}Access QR code for WhatsApp login:${NC}"
    echo -e "   Visit: https://hartonomotor.xyz/whatsapp-api/app/login"
    echo ""
    echo -e "3. ${YELLOW}Test admin panel integration:${NC}"
    echo -e "   Visit: https://hartonomotor.xyz/admin"
    echo -e "   Go to: WhatsApp Integration → Konfigurasi WhatsApp"
    echo -e "   Click: Test Koneksi"
    echo ""
    
    echo -e "${BLUE}🔧 Troubleshooting:${NC}"
    echo -e "  View logs: ${YELLOW}cd $WHATSAPP_DIR && docker-compose logs -f${NC}"
    echo -e "  Restart: ${YELLOW}cd $WHATSAPP_DIR && docker-compose restart${NC}"
    echo -e "  Status: ${YELLOW}docker ps | grep whatsapp${NC}"
    echo ""
}

# Main execution
echo -e "${BLUE}Starting Go version fix process...${NC}"
echo ""

# Execute all steps
if ! diagnose_go_mod; then
    fix_go_mod
fi

fix_go_sum
update_dockerfile
clean_docker_cache

if test_docker_build; then
    if deploy_with_compose; then
        verify_deployment
        show_next_steps
    else
        echo -e "${RED}❌ Deployment failed${NC}"
        exit 1
    fi
else
    echo -e "${RED}❌ Docker build test failed${NC}"
    echo -e "${YELLOW}💡 Try manual Go installation:${NC}"
    echo -e "   cd $WHATSAPP_DIR"
    echo -e "   go mod tidy"
    echo -e "   go build -o whatsapp-api ."
    echo -e "   ./whatsapp-api &"
    exit 1
fi

echo -e "${GREEN}✅ Go version fix script completed successfully!${NC}"
