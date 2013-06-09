== MailRelay Sync Plugin for Zen Cart ==

Version: 1.0
Author: MailRelay.es

Download latest version at: http://mailrelay.es/plugins/

== Description ==

This plugin allows you to sync your store's customers with your Mail Relay account subscribers into an specified group.

It was tested on version 1.5.1 and requires PHP compiled with CURL module.

== Installation ==

1. Backup all your data and files. This includes your database and website files.

2. Upload files on this zip to your Zen Cart installation following the folder structure.

3. Open file includes/modules/create_account.php and search for:

	// phpBB create account
	if ($phpBB->phpBB['installed'] == true) {
		$phpBB->phpbb_create_account($nick, $password, $email_address);
	}
	// End phppBB create account

Add after:

	// MailRelay Sync module
	if ( MRSYNC_AUTOSYNC )
	{
		// Instance MRSync class
		require( 'admin/'. DIR_WS_CLASSES .'mrsync.php' );
		$mr_sync = new MRSync();

		$mr_sync->initCurl(MRSYNC_URL, MRSYNC_KEY);
		$mr_sync->syncSubscriber( $email_address , $firstname .' '. $lastname , MRSYNC_GROUP );
	}

Remember you have to replace admin with the path where your control panel is set.

4. Open file includes/modules/pages/account_edit/header_php.php and search for:

	//update phpBB with new email address
	$old_addr_check=$db->Execute("select customers_email_address from ".TABLE_CUSTOMERS." where customers_id='".(int)$_SESSION['customer_id']."'");
	$phpBB->phpbb_change_email(zen_db_input($old_addr_check->fields['customers_email_address']),zen_db_input($email_address));

Add after:

	// MailRelay Sync module
	if ( MRSYNC_AUTOSYNC )
	{
		// Instance MRSync class
		require( 'admin/'. DIR_WS_CLASSES .'mrsync.php' );
		$mr_sync = new MRSync();

		$mr_sync->initCurl(MRSYNC_URL, MRSYNC_KEY);
		$mr_sync->syncSubscriber( $email_address , $firstname .' '. $lastname , MRSYNC_GROUP , $old_addr_check->fields['customers_email_address'] );
	}

Remember you have to replace admin with the path where your control panel is set.

5. Open the file admin/customers.php and search for:

        $db->Execute("delete from " . TABLE_WHOS_ONLINE . "
                      where customer_id = '" . (int)$customers_id . "'");

Add after:

        // MailRelay sync module
        if (MRSYNC_AUTOSYNC)
        {
                // read email from customer
                $customers = $db->Execute("SELECT c.customers_email_address FROM " . TABLE_CUSTOMERS . " c WHERE c.customers_id = '" . (int)$customers_id . "'");
                $email = $customers->fields["customers_email_address"];

                // instance mrsync class
                if ($email)
                {
                        require(DIR_WS_CLASSES.'mrsync.php');
                        $mr_sync = new MRSync();
                        $mr_sync->initCurl(MRSYNC_URL, MRSYNC_KEY);
                        $result = $mr_sync->deleteSubscriber($email);
                }
        }

Where admin is the path where your control panel is set.

6. Open file includes/filenames.php and search for:

	define('FILENAME_SQLPATCH','sqlpatch');

Add after:

	define('FILENAME_MRSYNC','mrsync');

7. Open file admin/includes/boxes/tools_dhtml.php and search for (*):

	$za_contents[] = array('text' => BOX_TOOLS_SQLPATCH, 'link' => zen_href_link(FILENAME_SQLPATCH, '', 'NONSSL'));

Add after:

	$za_contents[] = array('text' => BOX_TOOLS_MRSYNC, 'link' => zen_href_link(FILENAME_MRSYNC, '', 'NONSSL'));

Where admin is the path where your control panel is set.

8. (*) For ZenCart 1.5.0 and up it's not possible to do the previous step. Instead you need to update your database with the following query:

	INSERT INTO `zencart`.`admin_pages` (
	`page_key` ,
	`language_key` ,
	`main_page` ,
	`page_params` ,
	`menu_key` ,
	`display_on_menu` ,
	`sort_order`
	)
	VALUES (
	'mailrelaySelect', 'BOX_TOOLS_MRSYNC', 'FILENAME_MRSYNC', '', 'tools', 'Y', '20'
	);

9. Open file admin/includes/languages/en.php ( or es.php, depends of your language ) and search for:

	define('BOX_TOOLS_EZPAGES','EZ-Pages');

Add after:

	define('BOX_TOOLS_MRSYNC','MailRelay Sync');

Where admin is the path where your control panel is set.

10. You have finished module installation. Check configuring section to understand how to configure and use this plugin.


== Configuring ==

1. To configure plugin access your admin and go to Tools -> MailRelay Sync.

2. If this is the first time that you are using, you will be asked to confirm installation. Just click on the install button.

3. After installation, you will have to inform the following fields:

- Account URL
- API Key

You must make sure that the username have API permissions, otherwise you will get a permission error.

4. Submit the form and it will verify if your credentials are ok. If you run into any errors, double check the values that you entered.

5. Now select the group that you want to have customers synced. You can also enable auto sync, which will automatically add new subscribers
when a new customer register on your store. It will also keep name/email synced for current customers when they edit their data.

6. If you already have customers on your database, check field to manually sync your current customers.

7. Save form and wait. If you selected to manually sync customers, this operation can take several minutes.

== Uninstall ==

Follow these steps to uninstall this plugin:

1. Remove uploaded files during installation process

2. Remove code changes done during installation process

3. Remove configuration group on database for MRSync.
