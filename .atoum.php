<?php

use \mageekguy\atoum;

// Write all on stdout.
$stdOutWriter = new atoum\writers\std\out();

// Generate a CLI report.
$cliReport = new atoum\reports\realtime\cli();
$cliReport->addWriter($stdOutWriter);

$runner->addTestsFromDirectory('tests/unit/text.wiki2xhtml');
$runner->addReport($cliReport);
