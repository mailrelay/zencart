== MailRelay Sync Plugin para Zen Cart ==

Versión: 1.0
Autor: MailRelay.es

Descarge versión más reciente en: http://mailrelay.es/plugins/

== Descripción ==

Este plugin te permite sincronizar los clientes de su tienda con su cuenta Mail Relay en un grupo determinado.

Fue probado en la versión 1.5.1 y requiere PHP compilado con el módulo CURL.

== Instalación ==

1. Efectue una copia de seguridad de todos sus datos y archivos. Esto incluye su base de datos y archivos de página web.

2. Cargar archivos del archivo zip para su instalación Zen Cart siguiendo de la estructura de carpetas.

3. Abrir el archivo includes/modules/create_account.php y buscar por:

	// phpBB create account
	if ($phpBB->phpBB['installed'] == true) {
		$phpBB->phpbb_create_account($nick, $password, $email_address);
	}
	// End phppBB create account

Después de eso, inserte:

	// MailRelay Sync module
	if ( MRSYNC_AUTOSYNC )
	{
		// Instance MRSync class
		require( 'admin/'. DIR_WS_CLASSES .'mrsync.php' );
		$mr_sync = new MRSync();

		$mr_sync->initCurl(MRSYNC_URL, MRSYNC_KEY);
		$mr_sync->syncSubscriber( $email_address , $firstname .' '. $lastname , MRSYNC_GROUP );
	}

Recuerde que ha de sustituir 'admin' por el directorio donde usted tenga ubicado su panel de control.

4. Abrir el archivo includes/modules/pages/account_edit/header_php.php y buscar por:

	//update phpBB with new email address
	$old_addr_check=$db->Execute("select customers_email_address from ".TABLE_CUSTOMERS." where customers_id='".(int)$_SESSION['customer_id']."'");
	$phpBB->phpbb_change_email(zen_db_input($old_addr_check->fields['customers_email_address']),zen_db_input($email_address));

Después de eso, inserte:

	// MailRelay Sync module
	if ( MRSYNC_AUTOSYNC )
	{
		// Instance MRSync class
		require( 'admin/'. DIR_WS_CLASSES .'mrsync.php' );
		$mr_sync = new MRSync();

		$mr_sync->initCurl(MRSYNC_URL, MRSYNC_KEY);
		$mr_sync->syncSubscriber( $email_address , $firstname .' '. $lastname , MRSYNC_GROUP , $old_addr_check->fields['customers_email_address'] );
	}

Recuerde que ha de sustituir 'admin' por el directorio donde usted tenga ubicado su panel de control.

5. Abrir el archivo admin/customers.php y buscar por:

        $db->Execute("delete from " . TABLE_WHOS_ONLINE . "
                      where customer_id = '" . (int)$customers_id . "'");

Después de eso, inserte:

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

Donde admin será el directorio donde usted tenga ubicado su panel de control.

6. Abrir el archivo includes/filenames.php y buscar por:

	define('FILENAME_SQLPATCH','sqlpatch');

Después de eso, inserte:

	define('FILENAME_MRSYNC','mrsync');

7. Abrir el archivo admin/includes/boxes/tools_dhtml.php y buscar por (*):

	$za_contents[] = array('text' => BOX_TOOLS_SQLPATCH, 'link' => zen_href_link(FILENAME_SQLPATCH, '', 'NONSSL'));

Después de eso, inserte:

	$za_contents[] = array('text' => BOX_TOOLS_MRSYNC, 'link' => zen_href_link(FILENAME_MRSYNC, '', 'NONSSL'));

Donde admin será el directorio donde usted tenga ubicado su panel de control.

8.(*) A partir de la versión 1.5.0 y superiores de ZenCart no es posible realizar el paso anterior. En su lugar es necesario actualizar la base de datos con la siguiente consulta:

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

9. Abrir el archivo admin/includes/languages/en.php ( o es.php, depende de su lengua ) y buscar por:

	define('BOX_TOOLS_EZPAGES','EZ-Pages');

Después de eso, inserte:

	define('BOX_TOOLS_MRSYNC','MailRelay Sync');

Donde admin será el directorio donde usted tenga ubicado su panel de control.

10. Ha finalizado la instalación del módulo. Compruebe la sección de configuración para saber cómo configurar y utilizar esta aplicación.


== Configuración ==

1. Para configurar el plugin accesse su admin y ve a Herramientas -> MailRelay Sync.

2. Si esta es la primera vez que está utilizando, se le pedirá que confirme la instalación. Simplemente haga clic en el botón Instalar.

3. Después de la instalación, tendrá que informar a los siguientes campos:

- URL de su cuenta
- Clave API

Debe asegurarse de que el usuario tiene permisos de API, de lo contrario obtendrá un error de permiso.

4. Envíe el formulario y se verificará si sus credenciales son aceptables. Si encuentra algún error, vuelva a comprobar los valores que ha introducido.

5. Ahora seleccione el grupo que desea tener sus clientes sincronizados. También puede habilitar la sincronización automática, que automáticamente agregará los nuevos clientes.
Asimismo, mantendrá el nombre e/o correo electrónico sincronizado para los clientes actuales al editar sus datos.

6. Si usted ya tiene clientes en su base de datos, marque el campo para sincronizar manualmente sus clientes actuales.

7. Envie el formulario y aguarde. Si ha seleccionado la sincronización de clientes, esta operación puede tardar varios minutos.

== Desinstalación ==

Siga estos pasos para desinstalar este plugin:

1. Quitar los archivos cargados durante el proceso de instalación

2. Retire los cambios en el código efectuados durante el proceso de instalación

3. Elimine el grupo de configuración del MRSync en la base de datos.
