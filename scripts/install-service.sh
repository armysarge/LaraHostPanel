#!/bin/bash
# Install LaraHostPanel as a systemd service.
# Run with: sudo bash scripts/install-service.sh

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
WORKDIR="$(dirname "$SCRIPT_DIR")"
SERVICE_NAME="larahostpanel"
SERVICE_FILE="/etc/systemd/system/${SERVICE_NAME}.service"
TEMPLATE="$WORKDIR/larahostpanel.service"

if [[ $EUID -ne 0 ]]; then
    echo "Error: This script must be run as root."
    echo "Usage: sudo bash scripts/install-service.sh"
    exit 1
fi

# Use the user who invoked sudo, not root
INSTALL_USER="${SUDO_USER:-$USER}"
if [[ "$INSTALL_USER" == "root" ]]; then
    echo "Error: Do not install the service as root. Run with sudo from your regular user account."
    exit 1
fi

INSTALL_HOME="$(getent passwd "$INSTALL_USER" | cut -d: -f6)"

echo "Installing LaraHostPanel systemd service..."
echo "  User:    $INSTALL_USER"
echo "  Home:    $INSTALL_HOME"
echo "  WorkDir: $WORKDIR"
echo ""

# Substitute placeholders and write to system directory
sed \
    -e "s|__USER__|$INSTALL_USER|g" \
    -e "s|__WORKDIR__|$WORKDIR|g" \
    -e "s|__HOME__|$INSTALL_HOME|g" \
    "$TEMPLATE" > "$SERVICE_FILE"

chmod 644 "$SERVICE_FILE"

systemctl daemon-reload
systemctl enable "$SERVICE_NAME"

echo "Service installed successfully."
echo ""
echo "  Start now:    sudo systemctl start $SERVICE_NAME"
echo "  Check status: sudo systemctl status $SERVICE_NAME"
echo "  View logs:    sudo journalctl -u $SERVICE_NAME -f"
