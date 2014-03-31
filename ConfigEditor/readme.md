## ConfigEditor ##
The extension for Bolt which implements editing of variables by the End-User.

## Enable  ##
To use this extension, add it to enabled_extensions in your config.yml, like so:

    enabled_extensions: [ ConfigEditor ]

In the config.yml in the extension directory set value "path" and "parameters"

path - is the route for ConfigEditor
parameters - is the yml-path of config.

## Example ##
For example I want to changing Phone number in my site. I create in application`s config.yml some variable, for example:

	some_values:
		phone: 8888888
In the template I just use `{{ app.config.get('general/some_values/phone }}`.
But it is not good to grant access to config.yml to the End-User. 

To create "visual editing" to the some_values write in the extension`s config.yml
    
    path: [what you want]
	parameters: general/some_values