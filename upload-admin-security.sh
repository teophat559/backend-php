#!/bin/bash

# UPLOAD ADMIN SECURITY FILES TO VPS
# ===================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print status
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Configuration
VPS_IP="31.97.48.96"
VPS_USER="root"
VPS_PATH="/home/specialprogram2025.online/public_html"
LOCAL_PATH="./"

print_status "Bắt đầu upload admin security files lên VPS..."
print_status "VPS IP: $VPS_IP"
print_status "VPS Path: $VPS_PATH"

# 1. Create includes directory if not exists
print_status "Tạo thư mục includes trên VPS..."
ssh $VPS_USER@$VPS_IP "mkdir -p $VPS_PATH/includes"

# 2. Upload admin-security.php
print_status "Upload admin-security.php..."
scp $LOCAL_PATH/includes/admin-security.php $VPS_USER@$VPS_IP:$VPS_PATH/includes/

# 3. Upload verify-key.php
print_status "Upload verify-key.php..."
ssh $VPS_USER@$VPS_IP "mkdir -p $VPS_PATH/pages/admin"
scp $LOCAL_PATH/pages/admin/verify-key.php $VPS_USER@$VPS_IP:$VPS_PATH/pages/admin/

# 4. Upload emergency-admin-access.php
print_status "Upload emergency-admin-access.php..."
scp $LOCAL_PATH/emergency-admin-access.php $VPS_USER@$VPS_IP:$VPS_PATH/

# 5. Upload update-admin-security.sql
print_status "Upload update-admin-security.sql..."
scp $LOCAL_PATH/update-admin-security.sql $VPS_USER@$VPS_IP:$VPS_PATH/

# 6. Upload fix-permissions.sh
print_status "Upload fix-permissions.sh..."
scp $LOCAL_PATH/fix-permissions.sh $VPS_USER@$VPS_IP:$VPS_PATH/

# 7. Set permissions
print_status "Thiết lập quyền file..."
ssh $VPS_USER@$VPS_IP "chmod 644 $VPS_PATH/includes/admin-security.php"
ssh $VPS_USER@$VPS_IP "chmod 644 $VPS_PATH/pages/admin/verify-key.php"
ssh $VPS_USER@$VPS_IP "chmod 600 $VPS_PATH/emergency-admin-access.php"
ssh $VPS_USER@$VPS_IP "chmod 644 $VPS_PATH/update-admin-security.sql"
ssh $VPS_USER@$VPS_IP "chmod 755 $VPS_PATH/fix-permissions.sh"

# 8. Set ownership
print_status "Thiết lập quyền sở hữu..."
ssh $VPS_USER@$VPS_IP "chown specialprogram2025:specialprogram2025 $VPS_PATH/includes/admin-security.php"
ssh $VPS_USER@$VPS_IP "chown specialprogram2025:specialprogram2025 $VPS_PATH/pages/admin/verify-key.php"
ssh $VPS_USER@$VPS_IP "chown specialprogram2025:specialprogram2025 $VPS_PATH/emergency-admin-access.php"
ssh $VPS_USER@$VPS_IP "chown specialprogram2025:specialprogram2025 $VPS_PATH/update-admin-security.sql"
ssh $VPS_USER@$VPS_IP "chown specialprogram2025:specialprogram2025 $VPS_PATH/fix-permissions.sh"

# 9. Verify files
print_status "Kiểm tra file đã upload..."
ssh $VPS_USER@$VPS_IP "ls -la $VPS_PATH/includes/admin-security.php"
ssh $VPS_USER@$VPS_IP "ls -la $VPS_PATH/pages/admin/verify-key.php"
ssh $VPS_USER@$VPS_IP "ls -la $VPS_PATH/emergency-admin-access.php"

# 10. Update database
print_status "Cập nhật database..."
ssh $VPS_USER@$VPS_IP "mysql -u specialprogram -p123123zz@ specialprogram2025 < $VPS_PATH/update-admin-security.sql"

# 11. Run fix permissions
print_status "Chạy script cấp quyền..."
ssh $VPS_USER@$VPS_IP "cd $VPS_PATH && ./fix-permissions.sh"

# 12. Restart Apache
print_status "Khởi động lại Apache..."
ssh $VPS_USER@$VPS_IP "systemctl restart httpd"

print_success "=========================================="
print_success "ADMIN SECURITY FILES UPLOADED SUCCESSFULLY!"
print_success "=========================================="
print_status ""
print_status "📁 FILES UPLOADED:"
print_status "   - includes/admin-security.php"
print_status "   - pages/admin/verify-key.php"
print_status "   - emergency-admin-access.php"
print_status "   - update-admin-security.sql"
print_status "   - fix-permissions.sh"
print_status ""
print_status "🔐 ADMIN ACCESS:"
print_status "   URL: https://specialprogram2025.online/admin"
print_status "   Key: SP2025_ADMIN_SECURE_KEY_2025"
print_status ""
print_status "🚨 EMERGENCY ACCESS:"
print_status "   URL: https://specialprogram2025.online/emergency-admin-access.php"
print_status "   Key: EMERGENCY_ACCESS_2025"
print_status ""
print_success "✅ Admin security system ready!"
