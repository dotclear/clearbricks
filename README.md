# Clearbricks

## What is this?

No, Clearbricks is not yet another framework. Clearbricks is not cool at all and
does not even have a nice website. It won't make you smart neither have great
ideas for you.

Clearbricks is only about code and efficiency, consider it as a toolbox. And
please, do me a favor, don't call it a framework :-)

## How do I use it?

Clearbricks is about "using what we need when we need it". Pick the module(s)
you need and add it to your code. The only thing you'll always need is the
"common" directory.

Once you're ready, you have to create a \_common.php file on top of your
Clearbricks path (or wherever you want) and add `$__autoload` entries for your
modules.

Take a look at the \_common.php file.

Of course, you're encouraged to use the `$__autoload` magic for your own classes.

## Requirements

In order to use Clearbricks you need:

* PHP 5.4 with the following modules:
  * mbstring
  * iconv
  * simplexml

## Use it with Mercurial

You may think that all this subdirectories is a mess. You're quite right. But
one day, you'll find it cool to use Clearbricks modules in your mercurial
repository as [a subrepository][1]. This day, you'll love me :-)

Here is an example of using Clearbricks and dbLayer in your own repository
as an external property, using [subrepositories documentation][2]:

```sh
hg clone https://hg.clearbricks.org/hg path/to/clearbricks
echo 'path/to/clearbricks https://hg.clearbricks.org/hg' >> .hgsub
```

Save and push.

Then, you can create a \_common.php file wich will contain:

```php
<?php
require dirname(__FILE__).'/common/_main.php';
$__autoload['dbLayer'] = dirname(__FILE__).'/dblayer/dblayer.php';
```

You're done!

## API documentation

A [doxygen configuration file](http://www.stack.nl/~dimitri/doxygen/manual/config.html) is provided to generate the Clearbricks API documentation which will be [readable](doxygen/index.html) in doxygen folder:

```
doxygen .doxygen.conf
```

## Tests

Clearbricks classes are tested using [atoum][3] (see [doc][4]).
To run tests,

Clone this repository:

```sh
hg clone https://hg.clearbricks.org/hg path/to/clearbricks
```

Download the [`composer.phar`](https://getcomposer.org/composer.phar) executable or use the installer.

```sh
curl -sS https://getcomposer.org/installer | php
```

Update dependencies via composer:

```sh
composer.phar install
```

And now can run tests:

```sh
./bin/atoum
```

Add ```-ebpc``` option to enable path and branch coverage (this option requires xDebug 2.3+)

You can also get code coverage report (in `coverage/html`) for tests by runing:

```sh
./bin/atoum -c .atoum.coverage.php
```

## License

Copyright Olivier Meunier & Association Dotclear

[GPL-2.0-only](https://www.gnu.org/licenses/gpl-2.0.html)

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; version 2 of the License.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

[1]: http://mercurial.selenic.com/wiki/Subrepository
[2]: http://www.selenic.com/hg/help/subrepos
[3]: https://github.com/atoum/atoum
[4]: http://docs.atoum.org/en/latest/index.html
