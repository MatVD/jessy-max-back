#!/bin/bash
# Script de génération des secrets pour la production
# À exécuter en local avant le déploiement

echo "🔐 Génération des secrets de production..."
echo ""

# APP_SECRET
APP_SECRET=$(openssl rand -hex 32)
echo ""

# MERCURE_JWT_SECRET
MERCURE_JWT_SECRET=$(openssl rand -hex 32)
echo ""

# JWT_QRCODE_SECRET
JWT_QRCODE_SECRET=$(openssl rand -hex 32)
echo ""

# JWT_PASSPHRASE
JWT_PASSPHRASE=$(openssl rand -hex 16)
echo ""

# Génération des clés JWT
echo "📝 Génération des clés JWT..."
mkdir -p config/jwt
openssl genrsa -out config/jwt/private.pem -aes256 -passout pass:$JWT_PASSPHRASE 4096
openssl rsa -in config/jwt/private.pem -pubout -out config/jwt/public.pem -passin pass:$JWT_PASSPHRASE

echo ""
echo "✅ Clés JWT générées dans config/jwt/"
echo ""
echo "⚠️  IMPORTANT : Copie ces valeurs dans ton .env.prod et dans les GitHub Secrets !"