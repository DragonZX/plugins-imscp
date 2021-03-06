# Update errata

## Update to version 3.2.0

### Application section options

Many application section options were added or renamed. See the [CHANGELOG](CHANGELOG) for further details.

### Syslog proxy daemon ( syslogproxyd )

A syslog proxy daemon has been added, which allows to create log sockets ( /dev/log ) inside jails, whatever the Syslog
daemon implementation ( rsyslog, syslog-ng... ) in use. If one of your applications section require log socket, you must
just include the logbasics application section as follow:

```php
...
include_app_sections => array(
	'logbasics'
)
...
```

**Note:** The syslog proxy daemon can create up to 100 log sockets ATM. In near future, we'll remove this limitation by
using dynamic memory allocation.

## Update to version 3.1.2

### Dovecot 2.x

If you're using Dovecot on you server ( 2.x branch ), you could see some warning messages such as:

```
Jan 14 00:42:49 wheezy dovecot: master: Warning: /var/chroot/InstantSSH/vu2003/var/www/virtual/domain.tld is no longer mounted. See http://wiki2.dovecot.org/Mountpoints
```

Even if this is not a big issue, a routine has been added in this version to force Dovecot to ignore any mountpoints
mounted under the root directory of the jailed environments. However Dovecot will continue to warn you for the old
mountpoints which were detected previously. You can easily fix that issue by running the following command on your
system:

```bash
# doveadm mount remove /var/chroot/InstantSSH/*/var/www/virtual/*
```

**Note:** If you changed the paths in the plugin configuration file, you must adapt the command above.

## Update to version 3.1.0

### Application sections

## New php section

The **php** section allow to make PHP (cli) and some common PHP modules available inside the jails. To enable it, you
must add it to the **app_sections** option as follow:

```php
...
	'app_sections' => array(
		'imscpbase',
		'php'
	),
...
```

Once done, you must update the plugin list through the plugin interface to rebuild the jails.

### Application section options

#### Renamed copy_file_to option to jail_copy_file_to

The **copy_file_to** option which allow to copy a list of files inside the jails has been renamed to **jail_copy_file_to**.

#### New fstab option

This option allow to describe fstab entries to add into the **/etc/fstab** file. The filesystems specified in the fstab
option are automatically mounted inside the jails by the jail builder.

#### New jail_run_commands option

This option allow to execute a list of commands inside the jails once built or updated.

#### New sys_copy_file_to option

This option allow to copy a list of files outside the jails.

#### New sys_run_commands option

This option allow to execute a list of commands outside the jails once built or updated.

#### Removed mount option

The **mount** option has been removed in favor of the **fstab** option.

### New actions

A new action has been added in the admin/permissions interface, which allow the administrator to schedule rebuild of all
existent jails.

## Update to version 3.0.0

### Cascading permissions ( admin -> reseller -> customer )

Support for cascading permissions has been added. From now, administrators give SSH permissions to the resellers, and
the resellers give SSH permisssions to their customers according their own permissions.

### Multiple SSH users per customer

This new version add support for multiple SSH users per customer. Those users share the UID/GID of the i-MSCP unix
users (vuxxx), which in the context of the InstantSSH plugin are merely called **parent users**.

#### Parent users' usage

The parent users are still added in the jailed /etc/passwd files as first entry, but with the shell field set to
**/bin/false** to prevent any login from them. Doing this allow to always show the same user/group in the **ls** command
results. Indeed, this command always take the first entry matching the UID/GID from the /etc/passwd and /etc/group files.

#### SSH users's prefixes

A prefix is added to all SSH usernames to allow the administrator to filter them easily in the /etc/passwd file and also
to prevent customer to create SSH users with reserved names. The default prefix, which is set to **imscp_**, can be
modified by editing the **ssh_user_name_prefix** configuration parameter in the plugin configuration file. This parameter
does applies only to the newly created SSH users.

**Warning**: You must never set the **ssh_user_name_prefix** to an empty value. Doing this would allow the customers to
create unix users with reserved names.

### Password authentication capability

This new version also come with the password authentication capability which was missing in previous versions. The
passwords are encrypted in the database using the better available algorythm as provided by crypt(). For safety reasons,
this feature can be disabled by allowing only the passwordless authentication. This can be achieved by setting the
**passwordless_authentication** configuration parameter to **TRUE** into the plugin configuration file.

### Note regarding the system and database update

#### i-MSCP default user (vuxxx)

During update, the fields (homedir, shell) of the i-MSCP unix users (vuxxx) are reset back to their default values and
un-jailed if needed.

#### SSH keys entries

The ssh keys entries are automatically converted into SSH user entries where the SSH usernames are defined using the
prefix of SSH usernames and the SSH key unique identifier (eg. \<ssh_user_name_prefix\>1, \<ssh_user_name_prefix\>2...)

#### Reseller permissions

The SSH permissions for resellers which have customers with existent SSH permissions are automatically created using a
predefined set of permissions. After the plugin update, you should review those permissions if you want restrict the
resellers (eg. to force usage of jailed shells and/or forbid the edition of authentication options).

## Update to version 2.1.0

### Translation support

This new version add translation support. The plugin can now be translated in your language using a simple PHP file
which return an array of translation strings. In order, to translate this plugin in your language, you must:
 
1. Create a new translation file for your language (eg. de_DE.php) in the **plugins/InstantSSH/l10n** directory by
copying the **en_GB.php** file ( only if the file doesn't exist yet ). The file must be UTF-8 encoded.
2. Translate the strings (The keys are the msgid strings and the values the msgstr strings). You must only translate the
msgstr strings.

During your translation session, you must enable the **DEBUG** mode in the **/etc/imscp/imscp.conf** file to force reload
of the translation files on each request, else, the translation strings are put in cache. Don't forget to disable it once
you have finished to translate.

You're welcome to submit your translation files in our forum if you want see them integrated in the later release.
