#!/bin/bash
set -e

INSTALL_DIR="/var/www/html/admin/modules"
MODULE_DIR="$INSTALL_DIR/shikem_connector"

echo "Installing Shikem Connector for FreePBX..."

if [ ! -d "$INSTALL_DIR" ]; then
  echo "Error: FreePBX modules directory not found at $INSTALL_DIR"
  exit 1
fi

if [ ! -f "Shikem_Connector.module.php" ]; then
  echo "Error: Shikem_Connector.module.php not found in current directory"
  exit 1
fi

echo "Creating module directory..."
mkdir -p "$MODULE_DIR/views"
mkdir -p "$MODULE_DIR/lib"
mkdir -p "$MODULE_DIR/agi-bin"

echo "Copying files..."
cp -v manifest.xml "$MODULE_DIR/"
cp -v module.xml "$MODULE_DIR/"
cp -v Shikem_Connector.module.php "$MODULE_DIR/"
cp -v views/* "$MODULE_DIR/views/" 2>/dev/null || true
cp -v lib/* "$MODULE_DIR/lib/" 2>/dev/null || true
cp -v agi-bin/* "$MODULE_DIR/agi-bin/" 2>/dev/null || true

echo "Setting permissions..."
chown -R asterisk:asterisk "$MODULE_DIR"
chmod -R 755 "$MODULE_DIR"

echo "Installation complete!"
echo ""
echo "Next steps:"
echo "1. Log into FreePBX admin panel"
echo "2. Go to Admin > Module Admin"
echo "3. Find 'Shikem Connector' and enable it"
echo "4. Go to Settings > Integrations > Shikem Connector"
echo "5. Enter your Shikem API URL and temporary credentials"
