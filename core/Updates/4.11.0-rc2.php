<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Matomo\Updates;

use Matomo\Common;
use Matomo\Container\StaticContainer;
use Matomo\Db;
use Matomo\Matomo;
use Matomo\Plugins\UsersManager\Emails\UserInviteEmail;
use Matomo\Plugins\UsersManager\Model;
use Matomo\Site;
use Matomo\Updater;
use Matomo\Updater\Migration;
use Matomo\Updater\Migration\Factory as MigrationFactory;
use Matomo\Updates as PiwikUpdates;

/**
 * Update for version 4.11.0-rc2
 */
class Updates_4_11_0_rc2 extends PiwikUpdates
{
    private MigrationFactory $migration;

    private $pendingUsers;

    private $userTable;

    public function __construct(MigrationFactory $factory)
    {
        $this->migration = $factory;
        $this->userTable = Common::prefixTable('user');
    }

    /**
     * @return Migration[]
     */
    public function getMigrations(Updater $updater)
    {
        try {
            $this->pendingUsers = Db::fetchAll(
                "SELECT * FROM `$this->userTable` WHERE invite_status = ? ",
                ['pending']
            );
        } catch (\Exception $e) {
            // ignore any errors. The column might not exist when updating from an older version,
            // so there wouldn't be anything to update anyway
        }
        return [
          $this->migration->db->dropColumn('user', 'invite_status'),
          $this->migration->db->addColumns('user', ['invite_token' => 'VARCHAR(191) DEFAULT null']),
          $this->migration->db->addColumns('user', ['invited_by' => 'VARCHAR(100) DEFAULT null']),
          $this->migration->db->addColumns('user', ['invite_expired_at' => 'TIMESTAMP null DEFAULT null']),
          $this->migration->db->addColumns('user', ['invite_accept_at' => 'TIMESTAMP null DEFAULT null']),
        ];
    }

    public function doUpdate(Updater $updater)
    {
        $updater->executeMigrations(__FILE__, $this->getMigrations($updater));

        $model = new Model();
        if (!empty($this->pendingUsers)) {
            foreach ($this->pendingUsers as $user) {
                $model->deleteAllTokensForUser($user['login']);

                $site = $model->getSitesAccessFromUser($user['login']);
                if (isset($site[0])) {
                    $site = new Site($site[0]['site']);
                    $siteName = $site->getName();
                } else {
                    $siteName = "Default Site";
                }
                //generate Token
                $generatedToken = $model->generateRandomTokenAuth();

                //attach token to user
                $model->attachInviteToken($user['login'], $generatedToken, 7);

                // send email
                $email = StaticContainer::getContainer()->make(UserInviteEmail::class, [
                  'currentUser'  => Matomo::getCurrentUserLogin(),
                  'invitedUser'  => $user,
                  'siteName'     => $siteName,
                  'token'        => $generatedToken,
                  'expiryInDays' => 7,
                ]);
                $email->safeSend();
            }
        }
    }
}
