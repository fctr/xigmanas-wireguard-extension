git add .
git commit -m "Updated PHP"
git pull origin master
git push origin master
if "%1" == "x" goto EOF
scp /cygdrive/c/Users/Shaun.FCTR/Downloads/Apps/PortableGit/xigmanas-wireguard-extension/gui/wireguard-gui.php root@192.168.39.3:/mnt/nvme/extensions/wireguard/gui/wireguard-gui.php
scp /cygdrive/c/Users/Shaun.FCTR/Downloads/Apps/PortableGit/xigmanas-wireguard-extension/wireguard-init root@192.168.39.3:/mnt/nvme/extensions/wireguard/wireguard-init
scp /cygdrive/c/Users/Shaun.FCTR/Downloads/Apps/PortableGit/xigmanas-wireguard-extension/version root@192.168.39.3:/mnt/nvme/extensions/wireguard/version
