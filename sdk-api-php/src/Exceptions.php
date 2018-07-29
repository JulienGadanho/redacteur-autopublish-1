<?php namespace RSJG\Exceptions;

class SignatureInvalideException extends \Exception {
    protected $message = 'Signature invalide';
}

class ServiceInconnuException extends \Exception {
    protected $message = 'Service inconnu';
}

class PluginInactifException extends \Exception {
    protected $message = 'Plugin inactif ou désinstallé';
}

