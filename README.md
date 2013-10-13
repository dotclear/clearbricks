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

Once you're ready, you have to create a _common.php file on top of your
Clearbricks path (or wherever you want) and add `$__autoload` entries for your
modules.

Take a look at the _common.php file.

Of course, you're encouraged to use the `$__autoload` magic for your own classes.

## Use it with Mercurial

You may think that all this subdirectories is a mess. You're quite right. But
one day, you'll find it cool to use Clearbricks modules in your mercurial
repository as [a subrepository][1]. This day, you'll love me :-)

Here is an example of using Clearbricks and dbLayer in your own repository
as an external property, using [subrepositories documentation][2]:

    :::sh
    $ hg clone https://hg.clearbricks.org/hg path/to/clearbricks
    $ echo 'path/to/clearbricks https://hg.clearbricks.org/hg' >> .hgsub

Save and push.

Then, you can create a _common.php file wich will contain:

    :::php
    <?php
    require dirname(__FILE__).'/common/_main.php';
    $__autoload['dbLayer'] = dirname(__FILE__).'/dblayer/dblayer.php';


You're done!

## Tests

Clearbricks classes are tested using [atoum][3].
To run tests,

Clone this repository:
```
$ hg clone https://hg.clearbricks.org/hg path/to/clearbricks
```

Download the [`composer.phar`](https://getcomposer.org/composer.phar) executable or use the installer.

```
$ curl -sS https://getcomposer.org/installer | php
```

Update dependencies via composer:
```
$ composer.phar install
```

And now can run tests:
```
$ ./bin/atoum
```

You can also get code coverage for tests by runing:
```
$ ./bin/atoum -c .atoum.coverage.php
```


[1]: http://mercurial.selenic.com/wiki/Subrepository
[2]: http://www.selenic.com/hg/help/subrepos
[3]: https://github.com/atoum/atoum

