#!/bin/bash

# ========================================
# HORIZONS VOTING - APACHE CONFIG INSTALLER
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

# Check if running as root
check_root() {
    if [[ $EUID -ne 0 ]]; then
        print_error "Script này cần chạy với quyền root"
        exit 1
    fi
}

# Get domain from user
get_domain() {
    read -p "Nhập domain của bạn (ví dụ: example.com): " DOMAIN
    if [[ -z $DOMAIN ]]; then
        print_error "Domain không được để trống"
        exit 1
    fi
}

# Backup existing config
backup_config() {
    if [[ -f /etc/httpd/conf.d/horizons-voting.conf ]]; then
        print_status "Backup cấu hình cũ..."
        cp /etc/httpd/conf.d/horizons-voting.conf /etc/httpd/conf.d/horizons-voting.conf.backup.$(date +%Y%m%d_%H%M%S)
        print_success "Backup hoàn tất"
    fi
}

# Create web directory
create_web_directory() {
    print_status "Tạo thư mục website..."
    mkdir -p /home/specialprogram2025.online/public_html
    chown -R apache:apache /home/specialprogram2025.online/public_html
    chmod -R 755 /home/specialprogram2025.online/public_html
    print_success "Thư mục website đã được tạo: /home/specialprogram2025.online/public_html"
}

# Install Apache config
install_apache_config() {
    print_status "Cài đặt cấu hình Apache..."

    # Copy config file
    cp horizons-voting.conf /etc/httpd/conf.d/horizons-voting.conf

    # Replace domain in config
    sed -i "s/your-domain.com/$DOMAIN/g" /etc/httpd/conf.d/horizons-voting.conf

    # Set permissions
    chmod 644 /etc/httpd/conf.d/horizons-voting.conf
    chown root:root /etc/httpd/conf.d/horizons-voting.conf

    print_success "Cấu hình Apache đã được cài đặt"
}

# Test Apache config
test_apache_config() {
    print_status "Kiểm tra cấu hình Apache..."

    if apachectl configtest; then
        print_success "Cấu hình Apache hợp lệ"
    else
        print_error "Cấu hình Apache có lỗi"
        exit 1
    fi
}

# Restart Apache
restart_apache() {
    print_status "Khởi động lại Apache..."

    if systemctl restart httpd; then
        print_success "Apache đã được khởi động lại"
    else
        print_error "Không thể khởi động lại Apache"
        exit 1
    fi
}

# Check Apache status
check_apache_status() {
    print_status "Kiểm tra trạng thái Apache..."

    if systemctl is-active --quiet httpd; then
        print_success "Apache đang chạy"
    else
        print_error "Apache không chạy"
        exit 1
    fi
}

# Create uploads directory
create_uploads_directory() {
    print_status "Tạo thư mục uploads..."
    mkdir -p /home/specialprogram2025.online/public_html/uploads
    chown -R apache:apache /home/specialprogram2025.online/public_html/uploads
    chmod -R 777 /home/specialprogram2025.online/public_html/uploads
    print_success "Thư mục uploads đã được tạo"
}

# Show final information
show_final_info() {
    echo ""
    echo "=========================================="
    echo "🎉 CÀI ĐẶT APACHE CONFIG HOÀN TẤT!"
    echo "=========================================="
    echo ""
    echo "📋 Thông tin cấu hình:"
    echo "   - Domain: $DOMAIN"
    echo "   - Document Root: /home/specialprogram2025.online/public_html"
    echo "   - Config File: /etc/httpd/conf.d/specialprogram2025.conf"
    echo "   - Log Files: /var/log/httpd/specialprogram2025-*.log"
    echo ""
    echo "🔧 Lệnh hữu ích:"
    echo "   - Kiểm tra status: systemctl status httpd"
    echo "   - Restart Apache: systemctl restart httpd"
    echo "   - Xem log: tail -f /var/log/httpd/horizons-voting-error.log"
    echo "   - Test config: apachectl configtest"
    echo ""
    echo "⚠️  Lưu ý:"
    echo "   - Upload source code vào /home/specialprogram2025.online/public_html"
    echo "   - Cấu hình database trong config/database.php"
    echo "   - Cấu hình domain trong config/config.php"
    echo "   - Bỏ comment HTTPS config khi có SSL certificate"
    echo ""
    echo "🌐 Truy cập website:"
    echo "   - HTTP: http://$DOMAIN"
    echo "   - HTTPS: https://$DOMAIN (khi có SSL)"
    echo ""
}

# Main function
main() {
    echo "🚀 BẮT ĐẦU CÀI ĐẶT APACHE CONFIG"
    echo "=========================================="
    echo ""

    # Check if running as root
    check_root

    # Get domain
    get_domain

    # Start installation
    backup_config
    create_web_directory
    install_apache_config
    test_apache_config
    restart_apache
    check_apache_status
    create_uploads_directory
    show_final_info

    print_success "Cài đặt Apache config hoàn tất!"
}

# Run main function
main "$@"
