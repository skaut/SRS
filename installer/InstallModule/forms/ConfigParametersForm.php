<?php

declare(strict_types=1);

namespace App\InstallModule\Forms;

use Nette;
use Nette\Application\UI\Form;
use Nette\Http\Request;
use Nette\Neon\Neon;
use Skautis\Config;
use Skautis\Skautis;
use Skautis\User;
use Skautis\Wsdl\WebServiceFactory;
use Skautis\Wsdl\WsdlException;
use Skautis\Wsdl\WsdlManager;

/**
 * Formulář pro úpravu role.
 *
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 */
class ConfigParametersForm
{
    use Nette\SmartObject;

    /** @var BaseForm */
    private $baseFormFactory;

    /** @var Request */
    private $httpRequest;


    public function __construct(
        BaseForm $baseFormFactory,
        Request $httpRequest
    ) {
        $this->baseFormFactory      = $baseFormFactory;
        $this->httpRequest          = $httpRequest;
    }

    /**
     * Vytvoří formulář.
     */
    public function create() : Form
    {
        $form = $this->baseFormFactory->create();

        $form->addGroup('install.connection.mysql_db_settings');
        $form->addText('db_host', 'install.connection.host')
                ->addRule(Form::FILLED, 'install.connection.host_empty');
        $form->addText('db_username', 'install.connection.username')
                ->addRule(Form::FILLED, 'install.connection.username_empty');
        $form->addText('db_password', 'install.connection.password')
                ->addRule(Form::FILLED, 'install.connection.password_empty');
        $form->addText('db_name', 'install.connection.dbName')
                ->addRule(Form::FILLED, 'install.connection.dbName_empty');
        
        $form->addGroup('install.connection.skatIS_settings');
        $form->addText('skautISAppId', 'install.connection.skautISAppId')
                ->addRule(Form::FILLED, 'install.connection.skautISAppId_empty');
        $form->addCheckbox('skautISTestMode', 'install.connection.skautISTestMode');
        
        $form->addGroup('install.connection.mail_settings');
        $form->addCheckbox('smtp', 'install.connection.smtp')
            ->addCondition($form::EQUAL, true)
                ->toggle('smtp-host')
                ->toggle('smtp-port')
                ->toggle('smtp-username')
                ->toggle('smtp-password')
                ->toggle('smtp-secure');

        $form->addText('smtp_host', 'install.connection.host')
            ->setOption('id', 'smtp-host')
            ->addConditionOn($form['smtp'], Form::EQUAL, true)
                ->setRequired('install.connection.host_empty');
        $form->addText('smtp_port', 'install.connection.port')
            ->setOption('id', 'smtp-port')
            ->addConditionOn($form['smtp'], Form::EQUAL, true)
                ->setRequired('install.connection.port_empty');
        $form->addText('smtp_username', 'install.connection.username')
            ->setOption('id', 'smtp-username')
            ->addConditionOn($form['smtp'], Form::EQUAL, true)
                ->setRequired('install.connection.username_empty');
        $form->addText('smtp_password', 'install.connection.password')
            ->setOption('id', 'smtp-password')
            ->addConditionOn($form['smtp'], Form::EQUAL, true)
                ->setRequired('install.connection.password_empty');

        $items = [
            'null' => 'install.connection.smtp_secure.null',
            'ssl' => 'install.connection.smtp_secure.ssl',
            'tls' => 'install.connection.smtp_secure.tls'
        ];
        $form->addSelect('smtp_secure', 'install.connection.smtp_secure_type', $items)
            ->setOption('id', 'smtp-secure');
        
        $form->addSubmit('submit', 'admin.common.save');

        $form->onSuccess[] = [$this, 'processForm'];

        return $form;
    }

    /**
     * Zpracuje formulář.
     * @throws \Throwable
     */
    public function processForm(Form $form, \stdClass $values) : void
    {
        if(!$values->smtp) {
            $values->smtp_host     = '';
            $values->smtp_port     = '';
            $values->smtp_username = '';
            $values->smtp_password = '';
            $values->smtp_secure   = '';
        }

        try {
            $wsdlManager = new WsdlManager(new WebServiceFactory(), new Config($values->skautISAppId, $values->skautISTestMode));
            $skautIS     = new Skautis($wsdlManager, new User($wsdlManager));
            $skautIS->org->UnitAllRegistryBasic();
        } catch (WsdlException $ex) {
            $form->addError('install.admin.skautis_access_denied');
        }

        $config = new \Doctrine\DBAL\Configuration();
        $url = sprintf("mysql://%s:%s@%s/%s", $values->db_username, $values->db_password, $values->db_host, $values->db_name);
        $connectionParams = array('url' => $url);

        try {
            $conn = \Doctrine\DBAL\DriverManager::getConnection ($connectionParams, $config);   

            $conn->connect();
        } catch (\Exception $e) {
            bdump($e);
            $form->addError('install.connection.mysql_access_denied');
        }

        $params = [
            'parameters' => [
                'consoleUrl' => $this->httpRequest->getUrl()->hostUrl,
                'database' => [
                    'host' => $values->db_host,
                    'dbname' => $values->db_name,
                    'user' => $values->db_username,
                    'password' => $values->db_password
                ],
                'skautIS' => [
                    'appId' => $values->skautISAppId,
                    'test' => $values->skautISTestMode
                ]
            ],
            'mail' => [
                'smtp' => $values->smtp,
                'host' => $values->smtp_host,
                'port' => $values->smtp_port,
                'username' => $values->smtp_username,
                'password' => $values->smtp_password,
                'secure' => $values->smtp_secure
            ]
        ];
        $output = Neon::encode($params, Neon::BLOCK);
        $configNeon = __DIR__ . "/../../../app/config/config.local.neon";

        file_put_contents($configNeon, $output);
    }
}
