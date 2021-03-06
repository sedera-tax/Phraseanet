<?php

namespace Alchemy\Tests\Phrasea\Controller\Admin;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @group functional
 * @group legacy
 * @group authenticated
 * @group web
 */
class DataboxesTest extends \PhraseanetAuthenticatedWebTestCase
{
    protected $client;

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Databases::getDatabases
     * @covers Alchemy\Phrasea\Controller\Admin\Databases::connect
     * @dataProvider msgProvider
     */
    public function testGetSlash($type, $errorMsgId)
    {
        self::$DI['client']->request('GET', '/admin/databoxes/', [
            $type => $errorMsgId
        ]);

        $this->assertTrue(self::$DI['client']->getResponse()->isOk());
    }

    public function msgProvider()
    {
        return [
            ['error', 'already-started'],
            ['error', 'scheduler-started'],
            ['error', 'unknow'],
            ['error', 'bad-email'],
            ['error', 'special-chars'],
            ['error', 'base-failed'],
            ['error', 'database-failed'],
            ['error', 'no-empty'],
            ['success', 'restart'],
            ['success', 'mount-ok'],
            ['success', 'database-ok'],
        ];
    }

    /**
     * @covers Alchemy\Phrasea\Controller\Admin\Databases::getDatabases
     */
    public function testGetSlashUnauthorizedException()
    {
        $this->setAdmin(false);

        self::$DI['client']->request('GET', '/admin/databoxes/');

        $this->assertForbiddenResponse(self::$DI['client']->getResponse());
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::databaseMount
     */
    public function testMountBase()
    {
        $this->setAdmin(true);

        $base = $this->createDatabox();
        $base->unmount_databox();

        self::$DI['client']->request('POST', '/admin/databoxes/mount/', [
            'new_dbname' => 'unit_test_db'
        ]);

        $response = self::$DI['client']->getResponse();

        $this->assertTrue($response->isRedirect());
        $uriRedirect = $response->headers->get('location');

        $this->assertTrue(!!strrpos($uriRedirect, 'success=1'));
        $explode = explode('/', $uriRedirect);
        $databoxId = $explode[3];

        try {
            $databox = self::$DI['app']->findDataboxById($databoxId);
            $databox->unmount_databox();
            $databox->delete();
        } catch (NotFoundHttpException $e) {
            $this->fail('databox not mounted');
        }

        unset($databox);
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::createDatabase
     */
    public function testCreateDatabaseEmpty()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/databoxes/', [
            'new_dbname' => ''
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/admin/databoxes/?success=0&error=no-empty', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::createDatabase
     */
    public function testCreateDatabaseSpecialChar()
    {
        $this->setAdmin(true);

        self::$DI['client']->request('POST', '/admin/databoxes/', [
            'new_dbname' => 'ééààèè'
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $this->assertEquals('/admin/databoxes/?success=0&error=special-chars', $response->headers->get('location'));
    }

    /**
     * @covers \Alchemy\Phrasea\Controller\Admin\Database::createDatabase
     */
    public function testCreateDatabase()
    {
        $this->setAdmin(true);

        $this->createDatabase();

        self::$DI['client']->request('POST', '/admin/databoxes/', [
            'new_dbname'        => 'unit_test_db',
            'new_data_template' => 'fr-simple',
        ]);

        $response = self::$DI['client']->getResponse();
        $this->assertTrue($response->isRedirect());
        $uriRedirect = $response->headers->get('location');
        $this->assertTrue(!!strrpos($uriRedirect, 'success=1'));
        $explode = explode('/', $uriRedirect);
        $databoxId = $explode[3];
        $databox = self::$DI['app']->findDataboxById($databoxId);
        $databox->unmount_databox();
        $databox->delete();

        unset($stmt, $databox);
    }
}
