<?php

namespace App;

use Piwik\Piwik;

class Child extends Piwik\Base implements Piwik\Contracts\Contract
{
    public function method(?Piwik\Types\Nullable $a, Piwik\Types\A|Piwik\Types\B $b, Piwik\Types\A&Piwik\Types\B $c): Piwik\Types\Return
    {
        $x = new Piwik\Service();
        Piwik::postEvent('event', ['x' => 1]);
        if ($x instanceof Piwik\Service) {
            return Piwik\Types\Return::class;
        }
        try {
            throw new Piwik\Exceptions\Foo();
        } catch (Piwik\Exceptions\Foo $e) {
        }
        \Piwik\helperFunction();
    }
}

#[Piwik\Attributes\Marker]
class Attributed
{
}
-----
<?php

namespace App;

use Matomo\Matomo;

class Child extends Matomo\Base implements Matomo\Contracts\Contract
{
    public function method(?Matomo\Types\Nullable $a, Matomo\Types\A|Matomo\Types\B $b, Matomo\Types\A&Matomo\Types\B $c): Matomo\Types\Return
    {
        $x = new Matomo\Service();
        Matomo::postEvent('event', ['x' => 1]);
        if ($x instanceof Matomo\Service) {
            return Matomo\Types\Return::class;
        }
        try {
            throw new Matomo\Exceptions\Foo();
        } catch (Matomo\Exceptions\Foo $e) {
        }
        \Matomo\helperFunction();
    }
}

#[Matomo\Attributes\Marker]
class Attributed
{
}
