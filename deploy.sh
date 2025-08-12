#!/bin/bash

# 🚀 HORIZONS VOTING SYSTEM - AUTO DEPLOYMENT SCRIPT
# Tác giả: AI Assistant
# Phiên bản: 1.0.0

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DOMAIN="your-domain.com"
DB_NAME="your_db_name"
DB_USER="your_db_user"
DB_PASS="your_db_password"
WEB_ROOT="/home/specialprogram2025.online/public_html"
PHP_VERSION="8.3"

# Functions
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

# Detect OS
detect_os() {
    if [[ -f /etc/redhat-release ]]; then
        OS="redhat"
        print_status "Phát hiện hệ điều hành: RedHat/CentOS/AlmaLinux"
    elif [[ -f /etc/debian_version ]]; then
        OS="debian"
        print_status "Phát hiện hệ điều hành: Debian/Ubuntu"
    else
        print_error "Hệ điều hành không được hỗ trợ"
        exit 1
    fi
}

# Update system
update_system() {
    print_status "Cập nhật hệ thống..."
    if [[ $OS == "redhat" ]]; then
        dnf update -y
    else
        apt update && apt upgrade -y
    fi
    print_success "Hệ thống đã được cập nhật"
}

# Install Apache
install_apache() {
    print_status "Cài đặt Apache..."
    if [[ $OS == "redhat" ]]; then
        dnf install httpd -y
        systemctl start httpd
        systemctl enable httpd
    else
        apt install apache2 -y
        systemctl start apache2
        systemctl enable apache2
    fi
    print_success "Apache đã được cài đặt và khởi động"
}

# Install PHP
install_php() {
    print_status "Cài đặt PHP $PHP_VERSION..."
    if [[ $OS == "redhat" ]]; then
        dnf install epel-release -y
        dnf install https://rpms.remirepo.net/enterprise/remi-release-8.rpm -y
        dnf module enable php:remi-$PHP_VERSION -y
        dnf install php php-cli php-common php-mysqlnd php-zip php-gd php-mbstring php-curl php-xml php-pear php-bcmath php-json php-opcache php-pdo php-pdo-mysql -y
    else
        apt install software-properties-common -y
        add-apt-repository ppa:ondrej/php -y
        apt update
        apt install php$PHP_VERSION php$PHP_VERSION-cli php$PHP_VERSION-common php$PHP_VERSION-mysql php$PHP_VERSION-zip php$PHP_VERSION-gd php$PHP_VERSION-mbstring php$PHP_VERSION-curl php$PHP_VERSION-xml php$PHP_VERSION-bcmath php$PHP_VERSION-json php$PHP_VERSION-opcache php$PHP_VERSION-pdo -y
    fi
    print_success "PHP $PHP_VERSION đã được cài đặt"
}

# Install MySQL
install_mysql() {
    print_status "Cài đặt MySQL..."
    if [[ $OS == "redhat" ]]; then
        dnf install mysql-server -y
        systemctl start mysqld
        systemctl enable mysqld
    else
        apt install mysql-server -y
        systemctl start mysql
        systemctl enable mysql
    fi
    print_success "MySQL đã được cài đặt và khởi động"
}

# Configure firewall
configure_firewall() {
    print_status "Cấu hình Firewall..."
    if [[ $OS == "redhat" ]]; then
        firewall-cmd --permanent --add-service=http
        firewall-cmd --permanent --add-service=https
        firewall-cmd --reload
    else
        ufw allow 80
        ufw allow 443
        ufw --force enable
    fi
    print_success "Firewall đã được cấu hình"
}

# Create database and user
setup_database() {
    print_status "Thiết lập database..."

    # Secure MySQL installation
    if [[ $OS == "redhat" ]]; then
        mysql_secure_installation
    else
        mysql_secure_installation
    fi

    # Create database and user
    mysql -u root -p << EOF
CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '$DB_USER'@'localhost' IDENTIFIED BY '$DB_PASS';
GRANT ALL PRIVILEGES ON $DB_NAME.* TO '$DB_USER'@'localhost';
FLUSH PRIVILEGES;
EXIT;
EOF
    print_success "Database đã được thiết lập"
}

# Create web directory
create_web_directory() {
    print_status "Tạo thư mục website..."
    mkdir -p $WEB_ROOT
    print_success "Thư mục website đã được tạo: $WEB_ROOT"
}

# Configure Apache Virtual Host
configure_apache() {
    print_status "Cấu hình Apache Virtual Host..."

    cat > /etc/httpd/conf.d/horizons-voting.conf << EOF
<VirtualHost *:80>
    ServerName $DOMAIN
    ServerAlias www.$DOMAIN
    DocumentRoot $WEB_ROOT/php-version

    <Directory $WEB_ROOT/php-version>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog logs/horizons-voting-error.log
    CustomLog logs/horizons-voting-access.log combined
</VirtualHost>
EOF
    print_success "Apache Virtual Host đã được cấu hình"
}

# Set permissions
set_permissions() {
    print_status "Thiết lập quyền file..."
    chown -R apache:apache $WEB_ROOT
    chmod -R 755 $WEB_ROOT
    chmod -R 777 $WEB_ROOT/php-version/uploads
    print_success "Quyền file đã được thiết lập"
}

# Create admin user
create_admin_user() {
    print_status "Tạo admin user..."
    mysql -u $DB_USER -p$DB_PASS $DB_NAME << EOF
INSERT INTO users (username, email, password, full_name, role, status)
VALUES ('admin', 'admin@specialprogram2025.online', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 'active')
ON DUPLICATE KEY UPDATE id=id;
EOF
    print_success "Admin user đã được tạo (username: admin, password: password)"
}

# Create backup script
create_backup_script() {
    print_status "Tạo script backup..."

    cat > /root/backup-horizons.sh << EOF
#!/bin/bash
DATE=\$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/backup/horizons-voting"
mkdir -p \$BACKUP_DIR

# Backup database
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > \$BACKUP_DIR/db_\$DATE.sql

# Backup files
tar -czf \$BACKUP_DIR/files_\$DATE.tar.gz $WEB_ROOT

# Keep only last 7 days
find \$BACKUP_DIR -name "*.sql" -mtime +7 -delete
find \$BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: \$DATE"
EOF

    chmod +x /root/backup-horizons.sh

    # Add to crontab
    (crontab -l 2>/dev/null; echo "0 2 * * * /root/backup-horizons.sh") | crontab -

    print_success "Script backup đã được tạo và cấu hình cron job"
}

# Test installation
test_installation() {
    print_status "Kiểm tra cài đặt..."

    # Test Apache
    if systemctl is-active --quiet httpd; then
        print_success "Apache đang chạy"
    else
        print_error "Apache không chạy"
        return 1
    fi

    # Test MySQL
    if systemctl is-active --quiet mysqld; then
        print_success "MySQL đang chạy"
    else
        print_error "MySQL không chạy"
        return 1
    fi

    # Test PHP
    if php -v > /dev/null 2>&1; then
        print_success "PHP đang hoạt động"
    else
        print_error "PHP không hoạt động"
        return 1
    fi

    # Test database connection
    if mysql -u $DB_USER -p$DB_PASS -e "USE $DB_NAME;" > /dev/null 2>&1; then
        print_success "Kết nối database thành công"
    else
        print_error "Không thể kết nối database"
        return 1
    fi

    print_success "Tất cả kiểm tra đều thành công!"
}

# Show final information
show_final_info() {
    echo ""
    echo "=========================================="
    echo "🎉 TRIỂN KHAI HOÀN TẤT!"
    echo "=========================================="
    echo ""
    echo "📋 Thông tin cài đặt:"
    echo "   - Website: https://$DOMAIN"
    echo "   - Admin Panel: https://$DOMAIN/admin"
    echo "   - Admin Username: <set manually>"
    echo "   - Admin Password: <set manually>"
    echo ""
    echo "🗄️ Database:"
    echo "   - Database: $DB_NAME"
    echo "   - Username: $DB_USER"
    echo "   - Password: $DB_PASS"
    echo ""
    echo "📁 Thư mục:"
    echo "   - Web Root: $WEB_ROOT"
    echo "   - Logs: /var/log/httpd/"
    echo "   - Backup: /root/backup-horizons.sh"
    echo ""
    echo "🔧 Lệnh hữu ích:"
    echo "   - Kiểm tra status: systemctl status httpd"
    echo "   - Restart Apache: systemctl restart httpd"
    echo "   - Xem log: tail -f /var/log/httpd/error_log"
    echo "   - Backup thủ công: /root/backup-horizons.sh"
    echo ""
    echo "⚠️  Lưu ý:"
    echo "   - Đổi password admin ngay sau khi đăng nhập"
    echo "   - Cấu hình SSL certificate cho HTTPS"
    echo "   - Kiểm tra firewall và bảo mật"
    echo ""
}

# Main deployment function
main() {
    echo "🚀 BẮT ĐẦU TRIỂN KHAI HORIZONS VOTING SYSTEM"
    echo "=========================================="
    echo ""

    # Set domain for specialprogram2025
    # Customize domain before running
    DOMAIN="$DOMAIN"
    print_status "Sử dụng domain: $DOMAIN"
    if [[ -z $DOMAIN ]]; then
        print_error "Domain không được để trống"
        exit 1
    fi

    # Set database password for specialprogram2025
    # Customize DB_PASS before running
    DB_PASS="$DB_PASS"
    print_status "Database password đã được thiết lập"
    if [[ -z $DB_PASS ]]; then
        print_error "Database password không được để trống"
        exit 1
    fi

    # Check if running as root
    check_root

    # Detect OS
    detect_os

    # Start deployment
    update_system
    install_apache
    install_php
    install_mysql
    configure_firewall
    setup_database
    create_web_directory
    configure_apache
    set_permissions
    create_admin_user
    create_backup_script
    test_installation
    show_final_info

    print_success "Triển khai hoàn tất! Vui lòng upload source code vào thư mục $WEB_ROOT"
}

# Run main function
main "$@"
