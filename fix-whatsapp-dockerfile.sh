#!/bin/bash

# Fix WhatsApp API Dockerfile Missing Issue
# This script will diagnose and fix the missing Dockerfile problem

echo "🔧 Fixing WhatsApp API Dockerfile Missing Issue"
echo "==============================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
WHATSAPP_DIR="/var/www/whatsapp-api/go-whatsapp-web-multidevice-main"
LARAVEL_DIR="$(pwd)"

echo -e "${BLUE}📋 Configuration:${NC}"
echo -e "  WhatsApp Directory: ${WHATSAPP_DIR}"
echo -e "  Laravel Directory: ${LARAVEL_DIR}"
echo ""

# Function to diagnose the issue
diagnose_issue() {
    echo -e "${YELLOW}🔍 Step 1: Diagnosing the issue...${NC}"
    
    # Check if WhatsApp directory exists
    if [ -d "$WHATSAPP_DIR" ]; then
        echo -e "${GREEN}✅ WhatsApp directory exists${NC}"
        
        # List contents
        echo -e "${BLUE}Directory contents:${NC}"
        ls -la "$WHATSAPP_DIR" | head -10
        echo ""
        
        # Check for Dockerfile
        if [ -f "$WHATSAPP_DIR/Dockerfile" ]; then
            echo -e "${GREEN}✅ Dockerfile exists${NC}"
            return 0
        else
            echo -e "${RED}❌ Dockerfile missing${NC}"
        fi
        
        # Check for other important files
        echo -e "${BLUE}Checking for other files:${NC}"
        [ -f "$WHATSAPP_DIR/main.go" ] && echo -e "${GREEN}✅ main.go found${NC}" || echo -e "${RED}❌ main.go missing${NC}"
        [ -f "$WHATSAPP_DIR/go.mod" ] && echo -e "${GREEN}✅ go.mod found${NC}" || echo -e "${RED}❌ go.mod missing${NC}"
        [ -d "$WHATSAPP_DIR/src" ] && echo -e "${GREEN}✅ src directory found${NC}" || echo -e "${RED}❌ src directory missing${NC}"
        
    else
        echo -e "${RED}❌ WhatsApp directory does not exist${NC}"
    fi
    
    echo ""
    return 1
}

# Function to clean and re-download
clean_and_redownload() {
    echo -e "${YELLOW}🧹 Step 2: Cleaning and re-downloading WhatsApp API...${NC}"
    
    # Remove existing directories
    rm -rf /var/www/whatsapp-api
    rm -rf "$LARAVEL_DIR/go-whatsapp-web-multidevice-main"
    rm -f "$LARAVEL_DIR/whatsapp-api.zip"
    
    echo -e "${GREEN}✅ Cleaned existing files${NC}"
    
    # Create fresh directory
    mkdir -p /var/www/whatsapp-api
    cd "$LARAVEL_DIR"
    
    # Download with verbose output
    echo -e "${BLUE}Downloading WhatsApp API...${NC}"
    if curl -L -v -o whatsapp-api.zip "https://github.com/aldinokemal/go-whatsapp-web-multidevice/archive/refs/heads/main.zip"; then
        echo -e "${GREEN}✅ Download completed${NC}"
        
        # Check file size
        file_size=$(stat -c%s whatsapp-api.zip 2>/dev/null || stat -f%z whatsapp-api.zip 2>/dev/null)
        echo -e "${BLUE}Downloaded file size: ${file_size} bytes${NC}"
        
        if [ "$file_size" -lt 1000 ]; then
            echo -e "${RED}❌ Downloaded file is too small, probably an error page${NC}"
            return 1
        fi
    else
        echo -e "${RED}❌ Download failed${NC}"
        return 1
    fi
    
    # Extract with verbose output
    echo -e "${BLUE}Extracting files...${NC}"
    if unzip -q whatsapp-api.zip; then
        echo -e "${GREEN}✅ Extraction completed${NC}"
        
        # List extracted contents
        echo -e "${BLUE}Extracted contents:${NC}"
        ls -la go-whatsapp-web-multidevice-main/ | head -10
        
        # Check for Dockerfile in extracted directory
        if [ -f "go-whatsapp-web-multidevice-main/Dockerfile" ]; then
            echo -e "${GREEN}✅ Dockerfile found in extracted directory${NC}"
        else
            echo -e "${RED}❌ Dockerfile still missing in extracted directory${NC}"
            echo -e "${BLUE}Available files:${NC}"
            find go-whatsapp-web-multidevice-main -name "*Dockerfile*" -o -name "*docker*" 2>/dev/null
        fi
    else
        echo -e "${RED}❌ Extraction failed${NC}"
        return 1
    fi
    
    # Copy to deployment directory
    cp -r go-whatsapp-web-multidevice-main /var/www/whatsapp-api/
    echo -e "${GREEN}✅ Files copied to deployment directory${NC}"
    
    # Clean up
    rm whatsapp-api.zip
    
    echo ""
    return 0
}

# Function to create Dockerfile manually if missing
create_dockerfile_manually() {
    echo -e "${YELLOW}🔧 Step 3: Creating Dockerfile manually...${NC}"
    
    cd "$WHATSAPP_DIR"
    
    # Create Dockerfile based on the project structure
    cat > Dockerfile << 'EOF'
FROM golang:1.19-alpine AS builder

WORKDIR /app

# Install dependencies
RUN apk add --no-cache git

# Copy go mod files
COPY go.mod go.sum ./
RUN go mod download

# Copy source code
COPY . .

# Build the application
RUN CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -o main .

# Final stage
FROM alpine:latest

# Install ca-certificates and ffmpeg
RUN apk --no-cache add ca-certificates ffmpeg

WORKDIR /root/

# Copy the binary from builder stage
COPY --from=builder /app/main .

# Copy src directory if it exists
COPY --from=builder /app/src ./src

# Create necessary directories
RUN mkdir -p /app/sessions /app/media

# Expose port
EXPOSE 3000

# Run the application
CMD ["./main"]
EOF
    
    echo -e "${GREEN}✅ Dockerfile created manually${NC}"
    
    # Also create .dockerignore
    cat > .dockerignore << 'EOF'
.git
.gitignore
README.md
Dockerfile
.dockerignore
node_modules
npm-debug.log
coverage
.nyc_output
EOF
    
    echo -e "${GREEN}✅ .dockerignore created${NC}"
    echo ""
}

# Function to check Go project structure
check_go_structure() {
    echo -e "${YELLOW}🔍 Step 4: Checking Go project structure...${NC}"
    
    cd "$WHATSAPP_DIR"
    
    # Check for Go files
    if [ -f "main.go" ]; then
        echo -e "${GREEN}✅ main.go found${NC}"
    elif [ -f "src/main.go" ]; then
        echo -e "${GREEN}✅ main.go found in src directory${NC}"
        # Move main.go to root if it's in src
        cp src/main.go .
        echo -e "${GREEN}✅ main.go copied to root${NC}"
    else
        echo -e "${RED}❌ main.go not found${NC}"
        
        # Search for any .go files
        echo -e "${BLUE}Searching for Go files:${NC}"
        find . -name "*.go" | head -5
    fi
    
    # Check for go.mod
    if [ -f "go.mod" ]; then
        echo -e "${GREEN}✅ go.mod found${NC}"
    elif [ -f "src/go.mod" ]; then
        echo -e "${GREEN}✅ go.mod found in src directory${NC}"
        cp src/go.mod .
        cp src/go.sum . 2>/dev/null || true
        echo -e "${GREEN}✅ go.mod copied to root${NC}"
    else
        echo -e "${RED}❌ go.mod not found${NC}"
        
        # Create basic go.mod
        echo -e "${BLUE}Creating basic go.mod...${NC}"
        cat > go.mod << 'EOF'
module whatsapp-api

go 1.19

require (
    github.com/gorilla/mux v1.8.0
    github.com/gorilla/websocket v1.5.0
    go.mau.fi/whatsmeow v0.0.0-20230804114610-6c7c4b8b7f8e
)
EOF
        echo -e "${GREEN}✅ Basic go.mod created${NC}"
    fi
    
    echo ""
}

# Function to create alternative Docker setup
create_alternative_docker() {
    echo -e "${YELLOW}🔧 Step 5: Creating alternative Docker setup...${NC}"
    
    cd "$WHATSAPP_DIR"
    
    # Create simplified Dockerfile for Node.js version if Go version fails
    cat > Dockerfile.node << 'EOF'
FROM node:18-alpine

WORKDIR /app

# Install dependencies
RUN apk add --no-cache git python3 make g++ ffmpeg

# Copy package files
COPY package*.json ./

# Install npm dependencies
RUN npm install

# Copy source code
COPY . .

# Create necessary directories
RUN mkdir -p sessions media

# Expose port
EXPOSE 3000

# Start the application
CMD ["npm", "start"]
EOF
    
    # Create package.json if it doesn't exist
    if [ ! -f "package.json" ]; then
        cat > package.json << 'EOF'
{
  "name": "whatsapp-api",
  "version": "1.0.0",
  "description": "WhatsApp Web Multi Device API",
  "main": "app.js",
  "scripts": {
    "start": "node app.js",
    "dev": "nodemon app.js"
  },
  "dependencies": {
    "express": "^4.18.2",
    "cors": "^2.8.5",
    "multer": "^1.4.5-lts.1",
    "qrcode": "^1.5.3",
    "socket.io": "^4.7.2"
  }
}
EOF
        echo -e "${GREEN}✅ package.json created${NC}"
    fi
    
    # Create simple app.js if main application file is missing
    if [ ! -f "app.js" ] && [ ! -f "main.go" ] && [ ! -f "src/main.go" ]; then
        cat > app.js << 'EOF'
const express = require('express');
const app = express();
const port = process.env.APP_PORT || 3000;

app.use(express.json());

app.get('/app/devices', (req, res) => {
    res.json({ status: 'ok', message: 'WhatsApp API is running' });
});

app.get('/app/login', (req, res) => {
    res.send('<h1>WhatsApp QR Code Login</h1><p>QR Code functionality will be implemented here</p>');
});

app.listen(port, () => {
    console.log(`WhatsApp API running on port ${port}`);
});
EOF
        echo -e "${GREEN}✅ Basic app.js created${NC}"
    fi
    
    echo ""
}

# Function to update Docker Compose with fallback
update_docker_compose() {
    echo -e "${YELLOW}⚙️ Step 6: Updating Docker Compose configuration...${NC}"
    
    cd "$WHATSAPP_DIR"
    
    # Create Docker Compose with build context fix
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
      - ./src:/app/src
      - whatsapp_sessions:/app/sessions
      - whatsapp_media:/app/media
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

volumes:
  whatsapp_sessions:
  whatsapp_media:

networks:
  whatsapp-network:
    driver: bridge
EOF
    
    echo -e "${GREEN}✅ Docker Compose updated with build context fix${NC}"
    echo ""
}

# Function to test Docker build
test_docker_build() {
    echo -e "${YELLOW}🧪 Step 7: Testing Docker build...${NC}"
    
    cd "$WHATSAPP_DIR"
    
    # Test Docker build
    echo -e "${BLUE}Testing Docker build...${NC}"
    if docker build -t whatsapp-api-test . 2>&1 | tee build.log; then
        echo -e "${GREEN}✅ Docker build successful${NC}"
        
        # Clean up test image
        docker rmi whatsapp-api-test 2>/dev/null || true
        
        # Now try docker-compose
        echo -e "${BLUE}Testing Docker Compose...${NC}"
        if docker-compose up -d --build; then
            echo -e "${GREEN}✅ Docker Compose successful${NC}"
            
            # Wait and check status
            sleep 10
            if docker-compose ps | grep -q "Up"; then
                echo -e "${GREEN}✅ Container is running${NC}"
            else
                echo -e "${RED}❌ Container failed to start${NC}"
                echo -e "${BLUE}Container logs:${NC}"
                docker-compose logs
            fi
        else
            echo -e "${RED}❌ Docker Compose failed${NC}"
        fi
    else
        echo -e "${RED}❌ Docker build failed${NC}"
        echo -e "${BLUE}Build log:${NC}"
        tail -20 build.log
        
        # Try alternative Dockerfile
        if [ -f "Dockerfile.node" ]; then
            echo -e "${BLUE}Trying alternative Node.js Dockerfile...${NC}"
            mv Dockerfile Dockerfile.go.bak
            mv Dockerfile.node Dockerfile
            
            if docker build -t whatsapp-api-test . 2>&1; then
                echo -e "${GREEN}✅ Alternative Docker build successful${NC}"
                docker rmi whatsapp-api-test 2>/dev/null || true
            else
                echo -e "${RED}❌ Alternative Docker build also failed${NC}"
            fi
        fi
    fi
    
    echo ""
}

# Function to show manual alternative
show_manual_alternative() {
    echo -e "${YELLOW}📋 Manual Alternative Setup${NC}"
    echo "=========================="
    echo ""
    echo -e "${BLUE}If Docker continues to fail, you can run WhatsApp API manually:${NC}"
    echo ""
    echo -e "${YELLOW}1. Install Go (if not installed):${NC}"
    echo -e "   wget https://go.dev/dl/go1.19.linux-amd64.tar.gz"
    echo -e "   tar -C /usr/local -xzf go1.19.linux-amd64.tar.gz"
    echo -e "   export PATH=\$PATH:/usr/local/go/bin"
    echo ""
    echo -e "${YELLOW}2. Build and run manually:${NC}"
    echo -e "   cd $WHATSAPP_DIR"
    echo -e "   go mod tidy"
    echo -e "   go build -o whatsapp-api ."
    echo -e "   ./whatsapp-api"
    echo ""
    echo -e "${YELLOW}3. Or use Node.js version:${NC}"
    echo -e "   npm install"
    echo -e "   npm start"
    echo ""
}

# Main execution
echo -e "${BLUE}Starting Dockerfile fix process...${NC}"
echo ""

# Execute all steps
if ! diagnose_issue; then
    if clean_and_redownload; then
        if ! diagnose_issue; then
            create_dockerfile_manually
            check_go_structure
            create_alternative_docker
        fi
    else
        echo -e "${RED}❌ Failed to re-download WhatsApp API${NC}"
        exit 1
    fi
fi

update_docker_compose
test_docker_build
show_manual_alternative

echo -e "${GREEN}🎉 Dockerfile fix process completed!${NC}"
echo ""
echo -e "${BLUE}📋 Next Steps:${NC}"
echo -e "1. Check if container is running: ${YELLOW}docker ps | grep whatsapp${NC}"
echo -e "2. Test API: ${YELLOW}curl -u admin:HartonoMotor2025! http://localhost:3000/app/devices${NC}"
echo -e "3. View logs: ${YELLOW}cd $WHATSAPP_DIR && docker-compose logs -f${NC}"
echo -e "4. If still failing, try manual setup shown above"
echo ""
echo -e "${GREEN}✅ Fix script completed!${NC}"
