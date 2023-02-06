Installation Notes:
===================
**Since XigmaNAS release 7486, extensions are disabled by default. Before installing this extension, log into the web interface and choose** `System` **from the menu bar, then** `Advanced`. **If necessary, uncheck** `Disable scanning of folders for existing extension menus` **and click** `Save`.

Installation from Web Interface:
================================
Choose `Tools` from the menu bar, then `Command`. Assuming your persistent data folder is /mnt/data, enter this as the command and click `Execute`.
```bash
mkdir /mnt/data/extensions/wireguard && cd /mnt/data/extensions/wireguard && fetch https://raw.github.com/fctr/xigmanas-wireguard-extension/blob/master/wireguard-init && chmod +x wireguard-init && ./wireguard-init && rehash
```

Installation from SSH:
======================
You'll either need to log in as root, or once at a prompt, gain root by using sudo su. Assuming your persistent data folder is /mnt/data,
```bash
mkdir /mnt/data/extensions/wireguard
cd /mnt/data/extensions/wireguard
fetch https://raw.github.com/fctr/xigmanas-wireguard-extension/blob/master/wireguard-init
chmod +x wireguard-init
./wireguard-init && rehash
```
Description:
============
This is the XigmaNAS WireGuard Extension for embedded and full platforms.

Credits:
========
J.M. Rivera (JRGTH) script.
