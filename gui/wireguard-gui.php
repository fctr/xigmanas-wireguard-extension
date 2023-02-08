<?php
/*
	wireguard-gui.php

	WebGUI wrapper for the NAS4Free/XigmaNAS "WireGuard" add-on created by FCTR, (Copyright (c) 2023).

	Copyright (c) 2016 Andreas Schmidhuber
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice, this
	   list of conditions and the following disclaimer.
	2. Redistributions in binary form must reproduce the above copyright notice,
	   this list of conditions and the following disclaimer in the documentation
	   and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
	ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
	WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
	DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
	ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
	(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
	LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
	ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
	(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
	SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

	The views and conclusions contained in the software and documentation are those
	of the authors and should not be interpreted as representing official policies,
	either expressed or implied, of the NAS4Free Project.
*/
require("auth.inc");
require("guiconfig.inc");

$application = "WireGuard";
$pgtitle = array(gtext("Extensions"), "WireGuard");

// For NAS4Free 10.x versions.
$return_val = mwexec("/bin/cat /etc/prd.version | cut -d'.' -f1 | /usr/bin/grep '10'", true);
if ($return_val == 0) {
	if (is_array($config['rc']['postinit'] ) && is_array( $config['rc']['postinit']['cmd'] ) ) {
		for ($i = 0; $i < count($config['rc']['postinit']['cmd']);) { if (preg_match('/wireguard-init/', $config['rc']['postinit']['cmd'][$i])) break; ++$i; }
	}
}

// Initialize some variables.
//$rootfolder = dirname($config['rc']['postinit']['cmd'][$i]);
$confdir = "/var/etc/wireguardconf";
$cwdir = exec("/usr/bin/grep 'INSTALL_DIR=' {$confdir}/conf/wireguard_config | cut -d'\"' -f2");
$rootfolder = $cwdir;
$configfile = "{$rootfolder}/conf/wireguard_config";
$versionfile = "{$rootfolder}/version";
//$date = strftime('%c');                // Previous PHP versions, deprecated as of PHP 8.1.
$date = date('D M d h:i:s Y', time());   // Equivalent date replacement for the previous strftime function.
$logfile = "{$rootfolder}/log/wireguard_ext.log";
$logevent = "{$rootfolder}/log/wireguard_last_event.log";
$prdname = "wireguard";
$conffolder = "/usr/local/etc/wireguard";
$interfacename = "wg0";
$editing = (bool)false;

if ($rootfolder == "") $input_errors[] = gtext("Extension installed with fault");
else {
// Initialize locales.
	$textdomain = "/usr/local/share/locale";
	$textdomain_wireguard = "/usr/local/share/locale-wireguard";
	if (!is_link($textdomain_wireguard)) { mwexec("ln -s {$rootfolder}/locale-wireguard {$textdomain_wireguard}", true); }
	bindtextdomain("xigmanas", $textdomain_wireguard);
}
if (is_file("{$rootfolder}/postinit")) unlink("{$rootfolder}/postinit");

if ($_POST) {
	if(isset($_POST['edit']) && $_POST['edit']):
        $editing = (bool)true;
		$return_val = 0;
		$output = [];
	endif;
	if(isset($_POST['cancel']) && $_POST['cancel']):
        $editing = (bool)false;
		$return_val = 0;
		$output = [];
	endif;
	if(isset($_POST['apply']) && $_POST['apply']):
        // save changes here
        $editing = (bool)false;
		$return_val = 0;
		$output = [];
	endif;

	if(isset($_POST['upgrade']) && $_POST['upgrade']):
		$cmd = sprintf('%1$s/wireguard-init -u > %2$s',$rootfolder,$logevent);
		$return_val = 0;
		$output = [];
		exec($cmd,$output,$return_val);
		if($return_val == 0):
			ob_start();
			include("{$logevent}");
			$ausgabe = ob_get_contents();
			ob_end_clean(); 
			$savemsg .= str_replace("\n", "<br />", $ausgabe)."<br />";
		else:
			$input_errors[] = gtext('An error has occurred during upgrade process.');
			$cmd = sprintf('echo %s: %s An error has occurred during upgrade process. >> %s',$date,$application,$logfile);
			exec($cmd);
		endif;
	endif;

	// Remove only extension related files during cleanup.
	if (isset($_POST['uninstall']) && $_POST['uninstall']) {
		bindtextdomain("xigmanas", $textdomain);
		if (is_link($textdomain_wireguard)) mwexec("rm -f {$textdomain_wireguard}", true);
		if (is_dir($confdir)) mwexec("rm -Rf {$confdir}", true);
		mwexec("rm /usr/local/www/wireguard-gui.php && rm -R /usr/local/www/ext/wireguard-gui", true);
		mwexec("{$rootfolder}/wireguard-init -t", true);		
		$uninstall_cmd = "echo 'y' | wireguard-init -r";
		mwexec($uninstall_cmd, true);
		if (is_link("/usr/local/share/{$prdname}")) mwexec("rm /usr/local/share/{$prdname}", true);
		if (is_link("/var/cache/pkg")) mwexec("rm /var/cache/pkg", true);
		if (is_link("/var/db/pkg")) mwexec("rm /var/db/pkg && mkdir /var/db/pkg", true);
		
		// Remove postinit cmd in NAS4Free 10.x versions.
		$return_val = mwexec("/bin/cat /etc/prd.version | cut -d'.' -f1 | /usr/bin/grep '10'", true);
			if ($return_val == 0) {
				if (is_array($config['rc']['postinit']) && is_array($config['rc']['postinit']['cmd'])) {
					for ($i = 0; $i < count($config['rc']['postinit']['cmd']);) {
					if (preg_match('/wireguard-init/', $config['rc']['postinit']['cmd'][$i])) { unset($config['rc']['postinit']['cmd'][$i]); }
					++$i;
				}
			}
			write_config();
		}

		// Remove postinit cmd in NAS4Free later versions.
		if (is_array($config['rc']) && is_array($config['rc']['param'])) {
			$postinit_cmd = "{$rootfolder}/wireguard-init";
			$value = $postinit_cmd;
			$sphere_array = &$config['rc']['param'];
			$updateconfigfile = false;
		if (false !== ($index = array_search_ex($value, $sphere_array, 'value'))) {
			unset($sphere_array[$index]);
			$updateconfigfile = true;
		}
		if ($updateconfigfile) {
			write_config();
			$updateconfigfile = false;
		}
	}
	header("Location:index.php");
}

}

function get_all_conf() {
	global $conffolder;
	exec("find {$conffolder}/ -name \"*.conf\" -exec basename {} .conf \;", $result);
	return ($result);
}
function gen_prvkey() {
	exec("/usr/local/bin/wg genkey", $result);
	return ($result[0]);
}
function get_prvkey($conf) {
	global $conffolder;
	exec("/usr/bin/awk -F \"=\" '/PrivateKey/ {print $2 \"=\"}' {$conffolder}/{$conf}.conf | tr -d ' '", $result);
	return ($result[0]);
}
function gen_pubkey($prv_key) {
	exec("echo {$prv_key} | /usr/local/bin/wg pubkey", $result);
	return ($result[0]);
}
function get_pubkey($conf) {
	$pkey = get_prvkey($conf);
	exec("echo {$pkey} | /usr/local/bin/wg pubkey", $result);
	return ($result[0]);
}
function get_address($conf) {
	global $conffolder;
    global $editing;
	exec("/usr/bin/awk -F \"=\" '/Address/ {print $2}' {$conffolder}/{$conf}.conf | tr -d ' '", $result);
	if(empty($result)) {
		return($editing? "" : "(None Set)");
	}
	return ($result[0]);
}
function get_dns($conf) {
	global $conffolder;
    global $editing;
	exec("/usr/bin/awk -F \"=\" '/DNS/ {print $2}' {$conffolder}/{$conf}.conf | tr -d ' '", $result);
	if(empty($result)) {
		return($editing? "" : "(None Set)");
	}
	return ($result[0]);
}
function get_srvpubkey($conf) {
	global $conffolder;
    global $editing;
	exec("/usr/bin/awk -F \"=\" '/PublicKey/ {print $2 \"=\"}' {$conffolder}/{$conf}.conf | tr -d ' '", $result);
	if(empty($result)) {
		return($editing? "" : "(None Set)");
	}
	return ($result[0]);
}
function get_ips($conf) {
	global $conffolder;
    global $editing;
	exec("/usr/bin/awk -F \"=\" '/AllowedIPs/ {print $2}' {$conffolder}/{$conf}.conf | tr -d ' '", $result);
	if(empty($result)) {
		return($editing? "" : "(None Set)");
	}
	return ($result[0]);
}
function get_endpoint($conf) {
	global $conffolder;
    global $editing;
	exec("/usr/bin/awk -F \"=\" '/Endpoint/ {print $2}' {$conffolder}/{$conf}.conf | tr -d ' '", $result);
	if(empty($result)) {
		return($editing? "" : "(None Set)");
	}
	return ($result[0]);
}
function get_psk($conf) {
	global $conffolder;
    global $editing;
	exec("/usr/bin/awk -F \"=\" '/PresharedKey/ {print $2 \"=\"}' {$conffolder}/{$conf}.conf | tr -d ' '", $result);
	if(empty($result)) {
		return($editing? "" : "(None Set)");
	}
	return ($result[0]);
}
function get_mtu($conf) {
	global $conffolder;
    global $editing;
	exec("/usr/bin/awk -F \"=\" '/MTU/ {print $2}' {$conffolder}/{$conf}.conf | tr -d ' '", $result);
	if(empty($result)) {
		return($editing? "" : "(None Set)");
	}
	return ($result[0]);
}
function get_port($conf) {
	global $conffolder;
    global $editing;
	exec("/usr/bin/awk -F \"=\" '/ListenPort/ {print $2}' {$conffolder}/{$conf}.conf | tr -d ' '", $result);
	if(empty($result)) {
		return($editing? "" : "(None Set)");
	}
	return ($result[0]);
}
function is_active($conf) {
	exec("/sbin/ifconfig | grep {$conf}", $result);
	return !empty($result);
}
function startedonboot($conf) {
	exec("/usr/sbin/service -e | grep '/wireguard$'", $result);
	return !empty($result);
}
function startonboot($conf, $enableboot) {
    if ($enableboot) {
      exec("/usr/sbin/sysrc wireguard_interfaces=\"{$conf}\"", $result);
      if (substr($result, 0, 21) != "wireguard_interfaces:")
        return false;
      exec("/usr/sbin/service wireguard enable", $result);
      return (substr($result, 0, 17) == "wireguard enabled");
    } else {
      exec("/usr/sbin/service wireguard delete", $result);
      if (substr($result, 0, 24) != "wireguard_enable deleted")
        return false;
      exec("/usr/sbin/sysrc -x wireguard_interfaces", $result);
      return true;
    }
}
function get_keepalive($conf) {
	global $conffolder;
    global $editing;
	exec("/usr/bin/awk -F \"=\" '/PersistentKeepalive/ {print $2}' {$conffolder}/{$conf}.conf | tr -d ' '", $result);
	if(empty($result)) {
		return($editing? "" : "(None Set)");
	}
	return ($result[0]);
}

function get_version_ext() {
	global $versionfile;
	exec("/bin/cat {$versionfile}", $result);
	return ($result[0] ?? '');
}

function get_process_pid() {
	global $pidfile;
	exec("/bin/cat {$pidfile}", $state); 
	return ($state[0]);
}
/*
function enable_change($enable_change) {
	var endis = !(document.iform.enable.checked || enable_change);
	document.iform.start.disabled = endis;
	document.iform.stop.disabled = endis;
	document.iform.restart.disabled = endis;
	document.iform.backup.disabled = endis;
	document.iform.backup_path.disabled = endis;
	document.iform.backup_pathbrowsebtn.disabled = endis;
}
	*/

if (is_ajax()) {
	$getinfo['wireguard'] = get_version_wireguard();
	$getinfo['ext'] = get_version_ext();
	render_ajax($getinfo);
}

bindtextdomain("xigmanas", $textdomain);
include("fbegin.inc");
bindtextdomain("xigmanas", $textdomain_wireguard);
?>
<script type="text/javascript">//<![CDATA[
$(document).ready(function(){
	var gui = new GUI;
	gui.recall(0, 2000, 'wireguard-gui.php', null, function(data) {
		$('#getinfo').html(data.info);
		$('#getinfo_wireguard').html(data.wireguard);
		$('#getinfo_ext').html(data.ext);
	});
});
//]]>
</script>
<!-- The Spinner Elements -->
<script src="js/spin.min.js"></script>
<!-- use: onsubmit="spinner()" within the form tag -->
<script type="text/javascript">
<!--
}
//-->
</script>
<form action="wireguard-gui.php" method="post" name="iform" id="iform" onsubmit="spinner()">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr><td class="tabcont">
			<?php if (!empty($input_errors)) print_input_errors($input_errors);?>
			<?php if (!empty($savemsg)) print_info_box($savemsg);?>
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
				<?php html_titleline(gtext("General"));?>
				<?php html_text("installation_directory", gtext("Installation directory"), $rootfolder);?>
				<?php html_text("getinfo_ext", gtext("Extension version"), get_version_ext());?>
			</table>
			<div id="submit">
				<input name="upgrade" type="submit" class="formbtn" title="<?=gtext("Upgrade Extension and WireGuard Packages");?>" value="<?=gtext("Upgrade");?>" />
			</div>
			<br>
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
            <?php
				html_titleline(gtext("Interface"));
				html_text("int_name", gtext("Name"), $interfacename);
                if ($editing) {
                  html_checkbox("wg_boot",gtext("Start on Boot"),startedonboot($interfacename)); 
                  html_inputbox("int_prvkey", gtext("Private Key"), get_prvkey($interfacename), gtext("Leave blank to automatically generate a new one"),true,60,false);
                  html_inputbox("int_address", gtext("Address"), get_address($interfacename),gtext("Use CIDR format (i.e. 10.0.0.1/24)"),true,60,false);
                  html_inputbox("int_dns", gtext("DNS Servers"), get_dns($interfacename),gtext("Use a comma to separate multiple entries"),false,60,false);
                  html_inputbox("int_port", gtext("Listen Port"), get_port($interfacename),gtext("This should typically be left blank"),false,20,false);
                  html_inputbox("int_mtu", gtext("MTU"), get_mtu($interfacename),gtext("This should typically be left blank"),false,20,false);
                  html_titleline(gtext("Server"));
                  html_inputbox("pubkey", gtext("Public Key"), get_srvpubkey($interfacename),gtext("This should be copied from your WireGuard server"),true,60,false);
                  html_inputbox("pskkey", gtext("Pre-shared Key"), get_psk($interfacename),gtext("This should be copied from your WireGuard server"),false,60,false);
                  html_inputbox("ips", gtext("Allowed IPs"), get_ips($interfacename),gtext("Use CIDR format (i.e. 10.0.0.1/24) and a comma to separate multiple entries"),true,60,false);
                  html_inputbox("endpoint", gtext("Endpoint"), get_endpoint($interfacename),gtext("Enter FQDN or IP Address followed by a : and port (i.e. wg.company.com:51820)"),true,60,false);
                  html_inputbox("keepalive", gtext("Persisent Keepalive"), get_keepalive($interfacename),gtext("Seconds between pings. Typically left blank"),false,20,false);
                } else {
                  html_text("wg_active",gtext("Active"),(is_active($interfacename)? "Yes" : "No")); 
                  html_text("wg_boot",gtext("Start on Boot"),(startedonboot($interfacename)? "Yes" : "No")); 
                  html_text("int_pubkey", gtext("Public Key"), get_pubkey($interfacename));
                  html_text("int_address", gtext("Address"), get_address($interfacename));
                  html_text("int_dns", gtext("DNS Servers"), get_dns($interfacename));
                  html_text("int_port", gtext("Listen Port"), get_port($interfacename));
                  html_text("int_mtu", gtext("MTU"), get_mtu($interfacename));
                  html_titleline(gtext("Server"));
                  html_text("pubkey", gtext("Public Key"), get_srvpubkey($interfacename));
                  html_text("pskkey", gtext("Pre-shared Key"), get_psk($interfacename));
                  html_text("ips", gtext("Endpoint"), get_ips($interfacename));
                  html_text("endpoint", gtext("Endpoint"), get_endpoint($interfacename));
                  html_text("keepalive", gtext("Persisent Keepalive"), get_keepalive($interfacename));
                }
            ?>
			</table><br>
			<div id="submit1">
            <?php
            if ($editing) {
    		  echo "<input name=\"apply\" type=\"submit\" class=\"formbtn\" title=\"" . gtext("Apply") . "\" value=\"" . gtext("Apply") . "\" />&nbsp;";
    		  echo "<input name=\"cancel\" type=\"submit\" class=\"formbtn\" title=\"" . gtext("Cancel") . "\" value=\"" . gtext("Cancel") . "\" onclick=\"return confirm('" . gtext("Discard all changes?") . "')\" />";
            } else {
    		  echo "<input name=\"edit\" type=\"submit\" class=\"formbtn\" title=\"" . gtext("Edit") . "\" value=\"" . gtext("Edit") . "\" />";
            }
            ?>
			</div>
			<?php html_separator();?>
			<table width="100%" border="0" cellpadding="6" cellspacing="0">
				<?php html_titleline(gtext("Uninstall"));?>
				<?php html_separator();?>
			</table>
			<div id="submit1">
				<input name="uninstall" type="submit" class="formbtn" title="<?=gtext("Uninstall Extension and WireGuard packages completely");?>" value="<?=gtext("Uninstall");?>" onclick="return confirm('<?=gtext("WireGuard Extension and packages will be completely removed, ready to proceed?");?>')" />
			</div>
		</td></tr>
	</table>
	<?php include("formend.inc");?>
</form>
<script type="text/javascript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc");?>
