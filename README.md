# extendRemoteControl
Demo plugin to show how to extend remote control in API

## Installation

### Via GIT
- Go to your LimeSurvey Directory (version up to 2.06)
- Clone in plugins/extendRemoteControl directory

### Via ZIP dowload
- Get the file and uncompress it
- Move the file included to plugins/extendRemoteControl directory

## LimeSurvey configuration

### With LimeSurvey 2.06lts or lesser
To allow plugin to be called via RPC POST routine, you need to disable CsrfValidation for plugins/direct'. This can be done in application/config/config.php.
````
	'components' => array(
		'db' => array(
			[...]
		),
		'request' => array(
			'class'=>'LSHttpRequest',
			'noCsrfValidationRoutes'=>array(
				'remotecontrol',
				'plugins/direct'
		),
	[...]
	),
````

### With LimeSurvey 2.50 after build 160330 or 2.06_sp_1.0.9
The plugin can be used with newUnsecureRequest event

## Usage

You can call the new function with the link shown in plugin settings. If you publish API in LimeSurvey core config : another url is show where you can see all availablke function (the new one and the other from LimeSurvey core).

Since authentification and `get_session_key` is the same function, you can create the session key with the core remote control url and use it in the plugin remote control url. This allow different plugin with different function. Using same control key.

## Home page & Copyright
- HomePage <http://extensions.sondages.pro/extendremotecontrol/>
- Copyright © 2015-2016 Denis Chenu <http://sondages.pro>
- Licence : GNU General Public License <https://www.gnu.org/licenses/gpl-3.0.html>

## Support
- Issues <https://gitlab.com/SondagesPro/RemoteControl/extendRemoteControl/issues>
- Professional support <http://extensions.sondages.pro/1>
