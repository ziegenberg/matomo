<?php

class GeneratedNamespace
{
    public function getNamespaceDecl()
    {
        return 'namespace Piwik;';
    }

    public function getOtherNamespaceDecl()
    {
        return 'namespace PiwikPro;';
    }
}
-----
<?php

class GeneratedNamespace
{
    public function getNamespaceDecl()
    {
        return 'namespace Matomo;';
    }

    public function getOtherNamespaceDecl()
    {
        return 'namespace PiwikPro;';
    }
}
