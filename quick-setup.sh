#!/bin/bash

# ========================================
# SPECIALPROGRAM2025 - QUICK SETUP SCRIPT
# ========================================

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

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

# Configuration for specialprogram2025
DOMAIN="specialprogram2025.online"
DB_NAME="specialprogram2025"
DB_USER="specialprogram"
DB_PASS="123123zz@"
WEB_ROOT="/home/specialprogram2025.online/public_html"

# Check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "Script này cần chạy với quyền root"
        exit 1
    fi
}

# Setup database
setup_database() {
    print_status "Thiết lập database specialprogram2025..."

    # Run SQL setup
    mysql -u root -p < setup-database.sql

    print_success "Database đã được thiết lập thành công"
}

# Create web directory
create_web_directory() {
    print_status "Tạo thư mục website..."
    mkdir -p $WEB_ROOT

    # Copy source code if current directory contains it
    if [[ -f "index.php" ]]; then
        cp -r . $WEB_ROOT/
        print_success "Source code đã được copy"
    fi

    # Set permissions
    chown -R apache:apache $WEB_ROOT
    chmod -R 755 $WEB_ROOT
    chmod -R 777 $WEB_ROOT/uploads 2>/dev/null || mkdir -p $WEB_ROOT/uploads && chmod 777 $WEB_ROOT/uploads

    print_success "Thư mục website: $WEB_ROOT"
}

# Configure Apache
configure_apache() {
    print_status "Cấu hình Apache..."

    # Copy Apache config
    if [[ -f "horizons-voting.conf" ]]; then
        cp horizons-voting.conf /etc/httpd/conf.d/specialprogram2025.conf

        # Update paths in config
        sed -i "s|/var/www/html/horizons-voting/php-version|$WEB_ROOT|g" /etc/httpd/conf.d/specialprogram2025.conf
        sed -i "s|/var/www/html/specialprogram2025|$WEB_ROOT|g" /etc/httpd/conf.d/specialprogram2025.conf

        print_success "Apache config đã được cấu hình"
    fi
}

# Test and restart services
restart_services() {
    print_status "Restart services..."

    # Test Apache config
    if apachectl configtest; then
        systemctl restart httpd
        print_success "Apache đã được restart"
    else
        print_error "Apache config có lỗi"
        return 1
    fi

    # Check MySQL
    if systemctl is-active --quiet mysqld; then
        print_success "MySQL đang chạy"
    else
        systemctl restart mysqld
        print_success "MySQL đã được restart"
    fi
}

# Test installation
test_installation() {
    print_status "Kiểm tra cài đặt..."

    # Test database connection
    if mysql -u $DB_USER -p$DB_PASS -e "USE $DB_NAME; SELECT COUNT(*) FROM users;" > /dev/null 2>&1; then
        print_success "Kết nối database thành công"
    else
        print_error "Không thể kết nối database"
        return 1
    fi

    # Test website
    if [[ -f "$WEB_ROOT/index.php" ]]; then
        print_success "Website files đã sẵn sàng"
    else
        print_warning "Chưa có source code trong $WEB_ROOT"
    fi
}

# Show final information
show_final_info() {
    echo ""
    echo "=========================================="
    echo "🎉 SPECIALPROGRAM2025 SETUP HOÀN TẤT!"
    echo "=========================================="
    echo ""
    echo "📋 Thông tin hệ thống:"
    echo "   - Domain: https://$DOMAIN"
    echo "   - Website: $WEB_ROOT"
    echo "   - Database: $DB_NAME"
    echo "   - DB User: $DB_USER"
    echo "   - DB Pass: $DB_PASS"
    echo ""
    echo "👤 Admin Login:"
    echo "   - Username: admin"
    echo "   - Password: password"
    echo "   - Admin URL: https://$DOMAIN/admin"
    echo ""
    echo "📁 Các file quan trọng:"
    echo "   - Apache Config: /etc/httpd/conf.d/specialprogram2025.conf"
    echo "   - Database Config: $WEB_ROOT/config/database.php"
    echo "   - App Config: $WEB_ROOT/config/config.php"
    echo ""
    echo "🔧 Lệnh hữu ích:"
    echo "   - Restart Apache: systemctl restart httpd"
    echo "   - Check Apache: systemctl status httpd"
    echo "   - View logs: tail -f /var/log/httpd/specialprogram2025-error.log"
    echo "   - Test database: mysql -u $DB_USER -p$DB_PASS $DB_NAME"
    echo ""
    echo "⚠️  Cần làm tiếp:"
    echo "   - Upload source code vào $WEB_ROOT (nếu chưa có)"
    echo "   - Cấu hình SSL certificate cho HTTPS"
    echo "   - Đổi password admin sau khi đăng nhập"
    echo "   - Kiểm tra firewall settings"
    echo ""
    echo "🌐 Truy cập:"
    echo "   - Website: http://$DOMAIN (hoặc https nếu có SSL)"
    echo "   - Admin: http://$DOMAIN/admin"
    echo ""
}

# Main function
main() {
    echo "🚀 SPECIALPROGRAM2025 QUICK SETUP"
    echo "=================================="
    echo ""
    echo "Domain: $DOMAIN"
    echo "Database: $DB_NAME"
    echo "User: $DB_USER"
    echo ""

    # Check if running as root
    check_root

    # Ask for confirmation
    read -p "Tiếp tục setup? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Setup đã bị hủy"
        exit 0
    fi

    # Start setup
    setup_database
    create_web_directory
    configure_apache
    restart_services
    test_installation
    show_final_info

    print_success "Setup hoàn tất! 🎉"
}

# Run main function
main "$@"
