<?
/*
    ***** BEGIN LICENSE BLOCK *****
    
    This file is part of the Zotero Data Server.
    
    Copyright © 2012 Center for History and New Media
                     George Mason University, Fairfax, Virginia, USA
                     http://zotero.org
    
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU Affero General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.
    
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU Affero General Public License for more details.
    
    You should have received a copy of the GNU Affero General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
    ***** END LICENSE BLOCK *****
*/

require_once 'include/api.inc.php';
require_once 'include/sync.inc.php';

class SyncTests extends PHPUnit_Framework_TestCase {
	protected static $config;
	protected static $sessionID;
	
	public static function setUpBeforeClass() {
		require 'include/config.inc.php';
		foreach ($config as $k => $v) {
			self::$config[$k] = $v;
		}
		
		API::useFutureVersion(true);
	}
	
	
	public function setUp() {
		API::userClear(self::$config['userID']);
		API::groupClear(self::$config['ownedPrivateGroupID']);
		API::groupClear(self::$config['ownedPublicGroupID']);
		self::$sessionID = Sync::login();
	}
	
	
	public function tearDown() {
		Sync::logout(self::$sessionID);
		self::$sessionID = null;
	}
	
	
	public function testSyncEmpty() {
		$xml = Sync::updated(self::$sessionID);
		$this->assertEquals("0", (string) $xml['earliest']);
		$this->assertFalse(isset($xml->updated->items));
		$this->assertEquals(self::$config['userID'], (int) $xml['userID']);
	}
	
	
	/**
	 * @depends testSyncEmpty
	 */
	public function testSync() {
		$xml = Sync::updated(self::$sessionID);
		
		// Upload
		$data = file_get_contents("data/sync1upload.xml");
		$data = str_replace('libraryID=""', 'libraryID="' . self::$config['libraryID'] . '"', $data);
		
		$xml = Sync::updated(self::$sessionID);
		$updateKey = (string) $xml['updateKey'];
		
		$response = Sync::upload(self::$sessionID, $updateKey, $data);
		Sync::waitForUpload(self::$sessionID, $response, $this);
		
		// Download
		$xml = Sync::updated(self::$sessionID);
		unset($xml->updated->groups);
		$xml['timestamp'] = "";
		$xml['updateKey'] = "";
		$xml['earliest'] = "";
		
		$this->assertXmlStringEqualsXmlFile("data/sync1download.xml", $xml->asXML());
	}
}