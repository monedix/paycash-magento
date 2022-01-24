#Paycash-Pay

![Paycash Pay](https://realvirtual.com.mx/wp-content/uploads/2022/01/paycash_pay.png)

Módulo para pagos en efectivo conPaycash para Magento2 (soporte hasta v2.3.0)

## Instalación

1. Ir a la carpeta raíz del proyecto de Magento y seguir los siguiente pasos:

**Para versiones de Magento >= 2.3.5**
```bash
composer requirePaycash/magento:dev-master
```

2. Después se procede a habilitar el módulo,actualizar y limpiar cache de la plataforma.

```bash    
php bin/magento module:enable Paycash_Pay --clear-static-content
php bin/magento setup:upgrade
php bin/magento cache:clean
```

3. Para configurar el módulo desde el panel de administración de la tienda diríjase a: Stores > Configuration > Sales > Payment Methods