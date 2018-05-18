# PFRE

PFRE is a pf rule editor for OpenBSD. PFRE is expected to be used by beginners and system administrators alike.

You can find a couple of screenshots on the [wiki](https://github.com/sonertari/PFRE/wiki).

## Features

Using PFRE, you can develop rules from scratch or modify existing ones:

- Load, save, upload, download, and delete rule files.
- Display the rules in a tabular form, classified to their rule types and parsed into their individual elements.
- Add and delete rules, and move them within the ruleset.
- Edit rules in almost all possible detail: PFRE supports most, if not all of the BNF syntax specification in pf.conf(5).
- Test rules: PFRE validates all input and tests rules using pfctl to provide detailed error reports on-the-fly, while editing individual rules or displaying whole rulesets.
- Install the rules as the main ruleset, and activate them by loading into pf.

A couple of notes about the requirements, design decisions, and implementation of PFRE:

- PFRE does not provide any wizards nor tries to simplify rule development by hiding details. On the contrary, it enables the user by providing as much relevant detail as possible.
- PFRE aims to generate text ruleset output as close to what a system administrator would produce as possible:
	+ PFRE tries to be true to the original rule file loaded: PFRE does not insert any extra lines into its output, such as PFRE specific marks or rule generation dates (you cannot tell if its output is generated by PFRE or not).
	+ You can insert blank lines between rules: Blank lines are of a separate rule type.
	+ Comments are of a separate rule type too.
	+ All other rule types support inline comments.
- The edit page provides help links to relevant sections on the pf.conf(5) man page, which opens in a separate tab on your browser.
- PFRE uses gettext to support different languages, currently English and Turkish only.
- All important messages and test results are reported in error and information boxes.
- PFRE writes detailed logs to syslog, which you can filter into separate log files.
- PFRE uses MVC design to separate business logic from presentation, e.g. the View does not know how to parse, generate, validate or test pf rules (it is as thin or dumb as possible).
- PFRE has been tested using PHPUnit and Codeception.
- Source code is documented using Doxygen.
- PFRE takes security seriously:
	+ All input is untainted.
	+ Invalid rules are never tested using pfctl.
	+ Pfctl is executed in a separate process, which times out if pfctl takes too long.
	+ The Model is similar to the server of a privilege separation design. It defines and supports only a set of commands from the View.
	+ As the sole gatekeeper for the Model, PFRE controller, ctlr is the only executable enabled in the doas configuration. Ctlr validates all commands and their arguments given to it.
	+ The View executes all controller commands over an SSH connection.
	+ Passwords are never visible plain text anywhere.
	+ The View never reaches to the filesystem, nor runs any system executable (perhaps only /bin/sleep and /bin/date).
	+ All system executables are called using their full pathnames.
	+ The number of nested anchors in inline rules is restricted to a configurable maximum.
	+ JavaScript use is kept to a minimum.

## How to install

Here are the basic steps to obtain a working PFRE installation:

- Install OpenBSD 6.3, perhaps on a VM.
- Install PHP 7.0.28, php-pcntl, php-mcrypt, and php-cgi.
- Copy the files in PFRE src folder to /var/www/htdocs/pfre/.
- Configure httpd.conf for PFRE.
- Create admin and user users, and set their passwords.
- Enable ctlr.php in doas for admin and user users, and make sure ctlr.php is executable.
- Point your web browser to the web server and log in.

The following sections provide the details.

### Install OpenBSD

The OpenBSD installation guide is at [faq4](http://www.openbsd.org/faq/faq4.html).

Here are a couple of guidelines:

- You can download install63.iso available at OpenBSD mirrors.
- It may be easier to install a PFRE test system on a VM of your choice, e.g. VMware or VirtualBox, rather than bare hardware.
- 512MB RAM and 8GB HD should be more than enough.
- If you want to obtain a packet filtering firewall, make sure the VM has at least 2 ethernet interfaces:
	+ The external interface may obtain its IP address over DHCP
	+ The internal interface should have a static IP address
- You can simply accept the default disk layout and partitions suggested by the OpenBSD install script.
- You can safely leave out x\*, comp\*, and game\* install sets; you won't need them for a PFRE test system.

Reboot the system after installation is complete and log in as root.

### Install packages

Create a package cache folder:

	# cd /var/db/
	# mkdir pkg_cache

Set the $PKG\_PATH env variable to the cache folder you have just created:

	# export PKG_PATH=/var/db/pkg_cache/

Download the required packages from an OpenBSD mirror and copy them to $PKG\_PATH. The following is the list of files you should have under $PKG\_PATH:

	femail-1.0p1.tgz
	femail-chroot-1.0p2.tgz
	gettext-0.19.8.1p1.tgz
	libiconv-1.14p3.tgz
	libltdl-2.4.2p1.tgz
	libmcrypt-2.5.8p2.tgz
	libxml-2.9.8.tgz
	php-7.0.28.tgz
	php-cgi-7.0.28.tgz
	php-mcrypt-7.0.28.tgz
	php-pcntl-7.0.28.tgz
	xz-5.2.3p0.tgz

Install PHP, php-pcntl, php-mcrypt, and php-cgi by running the following commands, which should install their dependencies as well:

	# pkg_add -v php
	# pkg_add -v php-pcntl
	# pkg_add -v php-mcrypt
	# pkg_add -v php-cgi

If you want to see if all required packages are installed successfully, run the following command:

	# pkg_info -a

Here is the expected output of that command:

	femail-1.0p1        simple SMTP client
	femail-chroot-1.0p2 simple SMTP client for chrooted web servers
	gettext-0.19.8.1p1  GNU gettext runtime libraries and programs
	libiconv-1.14p3     character set conversion library
	libltdl-2.4.2p1     GNU libtool system independent dlopen wrapper
	libmcrypt-2.5.8p2   interface to access block/stream encryption algorithms
	libxml-2.9.8        XML parsing library
	php-7.0.28          server-side HTML-embedded scripting language
	php-cgi-7.0.28      cgi sapi for php
	php-mcrypt-7.0.28   mcrypt encryption/decryption extensions for php
	php-pcntl-7.0.28    PCNTL extensions for php
	xz-5.2.3p0          LZMA compression and decompression tools

### Install PFRE

Create a 'pfre' folder under /var/www/htdocs/ and copy all the contents of the PFRE src folder to /var/www/htdocs/pfre/. Their user permissions should be root:daemon.

Make sure /var/www/htdocs/pfre/Controller/ctlr.php is executable. If not, go to /var/www/htdocs/pfre/Controller/ and make it executable:

	# cd /var/www/htdocs/pfre/Controller/
	# chmod u+x ctlr.php

And create the folder for configuration files:

	# mkdir /etc/pfre/

#### Configure web server

Configure PFRE in httpd.conf. Note that we should disable chroot by chrooting to /. Your configuration might look like the following:

	chroot "/"
	#prefork 3

	server "pfre" {
		listen on * port 80
		listen on * tls port 443
		directory index "index.php"

		location "*.php" {
			fastcgi socket "/var/www/run/php-fpm.sock"
		}

		log syslog
		root "/var/www/htdocs/pfre/View/"
	}

Create a self-signed server certificate. Run the following commands to generate your own CA:

	# openssl genrsa -des3 -out ca.key 4096
	# openssl req -new -x509 -days 365 -key ca.key -out ca.crt

Next, to generate a server key and request for signing, run the following:

	# openssl genrsa -des3 -out server.key 4096
	# openssl req -new -key server.key -out server.csr

You should sign the certificate signing request (csr) with the self-created certificate authority (CA) that you
made earlier:

	# openssl x509 -req -days 365 -in server.csr -CA ca.crt -CAkey ca.key -set_serial 01 -out server.crt

To make a server.key which doesn't cause httpd to prompt for a password:

	# openssl rsa -in server.key -out server.key.insecure
	# mv server.key server.key.secure
	# mv server.key.insecure server.key

Finally, you should copy server.crt and server.key files to the default locations defined in httpd.conf(5):

	# cp server.key /etc/ssl/private/
	# cp server.crt /etc/ssl/

Run adduser(8) to create admin and user users, for example with the following values:

	Name:        admin
	Password:    ****
	Fullname:    PFRE admin
	Uid:         1000
	Gid:         1000 (admin)
	Groups:      admin 
	Login Class: default
	HOME:        /home/admin
	Shell:       /bin/ksh

	Name:        user
	Password:    ****
	Fullname:    PFRE user
	Uid:         1001
	Gid:         1001 (user)
	Groups:      user 
	Login Class: default
	HOME:        /home/user
	Shell:       /bin/ksh

Then set their passswords to soner123 by running the following commands (actually, to the sha1 hash of soner123, because passwords are double encrypted on PFRE):

	# /usr/bin/chpass -a "admin:$(/usr/bin/encrypt `/bin/echo -n soner123 | sha1 -`):1000:1000::0:0:PFRE admin:/home/admin:/bin/ksh"
	# /usr/bin/chpass -a "user:$(/usr/bin/encrypt `/bin/echo -n soner123 | sha1 -`):1001:1001::0:0:PFRE user:/home/user:/bin/ksh"

However, you are advised to pick a better password than soner123.

#### Configure PHP

Go to /usr/local/bin/ and create a link to php executable:

	# cd /usr/local/bin
	# ln -s php-7.0 php

Edit the /etc/php-7.0.ini file to disable NOTICE messages, otherwise they may disturb pfctl test reports:

	error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_NOTICE

To enable pcntl and mcrypt, go to /etc/php-7.0/ and create the pcntl.ini and mcrypt.ini files:

	# cd /etc/php-7.0/
	# touch pcntl.ini
	# touch mcrypt.ini

And add the following line to pcntl.ini:

	extension=pcntl.so

Then add the following line to mcrypt.ini:

	extension=mcrypt.so

Disable chroot in /etc/php-fpm.conf by commenting out the chroot line:

	;chroot = /var/www

If you want to use Turkish translations, you should first install the gettext-tools-0.19.8.1.tgz package to generate the gettext mo file:

	# cd /var/www/htdocs/pfre/View/locale/tr_TR/LC_MESSAGES/
	# msgfmt -o pfre.mo pfre.po

#### Configure doas

Go to /etc/ and create the doas.conf file:

	# cd /etc/
	# touch doas.conf

And add the following lines to it:

	permit nopass admin as root cmd /var/www/htdocs/pfre/Controller/ctlr.php
	permit nopass user as root cmd /var/www/htdocs/pfre/Controller/ctlr.php
	permit nopass keepenv root as root

#### Configure system

If you want the web server to be started automatically after a reboot, first copy the sample rc.local file to /etc/:

	# cd /etc/
	# cp examples/rc.local .

Then add the following lines to it:

	if [ -x /usr/local/sbin/php-fpm-7.0 ]; then
		echo 'PHP CGI server'
		/usr/local/sbin/php-fpm-7.0
	fi

Create the rc.conf.local file under /etc/

	# cd /etc/
	# touch rc.conf.local

And add the following line to it:

	httpd_flags=

Also, if you want to use this PFRE test system as a firewall, you should enable packet forwarding between interfaces in /etc/sysctl.conf. So, copy the sample sysctl.conf file under /etc/examples/ to /etc/:

	# cd /etc/
	# cp examples/sysctl.conf .

And uncomment the line which enables forwarding of IPv4 packets:

	net.inet.ip.forwarding=1

### Start PFRE

Now you can either reboot the system or start the php cgi server and the web server manually using the following commands:

	# /usr/local/sbin/php-fpm-7.0
	# /usr/sbin/httpd 

Finally, if you point your web browser to the IP address of PFRE, you should see the login page. And you should be able to log in by entering admin:soner123 as user and password.

