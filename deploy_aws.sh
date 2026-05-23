#!/bin/bash
# ============================================================
# SysMarket v2 — Script de despliegue en AWS EC2 Ubuntu 22.04
# Ejecutar como: bash deploy_aws.sh
# ============================================================
set -e
echo "============================================="
echo " SysMarket v2 — Setup AWS EC2"
echo "============================================="

echo "[1/6] Actualizando paquetes..."
sudo apt-get update -qq && sudo apt-get upgrade -y -qq

echo "[2/6] Instalando dependencias para Docker..."
sudo apt-get install -y ca-certificates curl gnupg git

echo "[3/6] Agregando repositorio oficial Docker..."
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo gpg --dearmor -o /etc/apt/keyrings/docker.gpg
sudo chmod a+r /etc/apt/keyrings/docker.gpg
echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

echo "[4/6] Instalando Docker Engine + Compose plugin..."
sudo apt-get update -qq
sudo apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker $USER

echo "[5/6] Configurando firewall (UFW)..."
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable

echo "[6/6] Habilitando Docker como servicio..."
sudo systemctl enable docker
sudo systemctl start docker

echo ""
echo "============================================="
echo " ✅ Setup completo"
echo "============================================="
echo " Siguientes pasos:"
echo "   1) Cierra y vuelve a abrir la sesión SSH"
echo "   2) cd al directorio del proyecto"
echo "   3) docker compose up -d --build"
echo "   4) Abre http://<IP_PUBLICA> en el navegador"
echo "============================================="
