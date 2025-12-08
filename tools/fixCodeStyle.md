# Fix code style

Developers may prepare their code style before checkin
JG uses its own codestyle. See github doc folder
The scripts could not achive all aspects but may improve over time

## Call the script

use fixCodeStyle.bat or fixCodeStyle.sh depending on operation system.
Start the script in tools folder.

php and composer must be available over the path environment.

The scxript will move to the root folder and call the following commands from there

1) Check if PHP is available
1) Move to jg_basePath
1) Verify that we are in the correct working directory
1) Install needed dependencies (composer)
1) Fix 01: call "php-cs-fixer"
1) Fix 02: call "fixindent"
1) Fix 03: call "phpcbf"
1) Fix 04: call "php-cs-fixer"
1) Move back

## Results

The code style is now improved as good as it gets

You will find three log files telling about the results

- 01.php-cs-fixer.log
- 02.fixindent.log
- 03.phpcbf.log
- 04.php-cs-fixer.log

These can be ignored

# On error of script

On error of script the actual folder may be on the root instead of tools
A call of ```popd``` in the command line will bring you back

# Further scripts

The scripts used may be called separately

* checkCodeBy_cs_fixer
* checkCodeBy_cs_cbf
* fixindent.php
