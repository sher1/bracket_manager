<?php

declare(strict_types=1);

namespace Drupal\Tests\bracket_manager\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Core\File\FileSystemInterface;
use Drupal\file\Plugin\migrate\source\d7\File as D7File;
use Drupal\KernelTests\KernelTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;

/**
 * Tests the Tournament content entity storage and rendering.
 */
class TournamentEntityTest extends KernelTestBase {

  /**
   * Tracks whether the browser output directory has been prepared.
   *
   * @var bool
   */
  protected static $browserOutputPrepared = FALSE;

  /**
   * {@inheritdoc}
   */
  public static function setUpBeforeClass(): void {
    parent::setUpBeforeClass();

    if (empty(getenv('BROWSERTEST_OUTPUT_DIRECTORY'))) {
      $public_path = 'public://';
      try {
        $reflection = new \ReflectionClass(D7File::class);
        $public_path = (string) $reflection->getStaticPropertyValue('publicPath');
      }
      catch (\ReflectionException $exception) {
        // Fallback to the default public scheme.
      }

      $output_path = rtrim($public_path, '/') . '/simpletest/browser_output';
      putenv("BROWSERTEST_OUTPUT_DIRECTORY={$output_path}");
    }
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'node',
    'bracket_manager',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->ensureBrowserOutputDirectory();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['bracket_manager']);
    $this->ensureParticipantContentType();
    $this->ensureTournamentContentType();
  }

  /**
   * {@inheritdoc}
   */
  protected function getDatabaseConnectionInfo() {
    $app_root = $this->root;
    $site_path = 'sites/default';
    $databases = [];

    $settings_file = $app_root . '/' . $site_path . '/settings.php';
    if (file_exists($settings_file)) {
      // Provide variables expected by settings.php.
      $GLOBALS['app_root'] = $app_root;
      $GLOBALS['site_path'] = $site_path;
      include $settings_file;
    }

    if (!empty($databases['default']['default'])) {
      Database::addConnectionInfo('default', 'default', $databases['default']['default']);
    }
    else {
      // Fallback to the parent behavior (SIMPLETEST_DB).
      return parent::getDatabaseConnectionInfo();
    }

    $connection_info = Database::getConnectionInfo('default');
    if (!empty($connection_info)) {
      Database::renameConnection('default', 'simpletest_original_default');
      foreach ($connection_info as $target => $value) {
        $connection_info[$target]['prefix'] = $this->databasePrefix;
      }
    }
    return $connection_info;
  }

  /**
   * Ensure browser output directory exists under public files.
   */
  protected function ensureBrowserOutputDirectory(): void {
    if (self::$browserOutputPrepared) {
      return;
    }
    $output_path = getenv('BROWSERTEST_OUTPUT_DIRECTORY');
    if ($output_path) {
      $this->container->get('file_system')->prepareDirectory(
        $output_path,
        FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
      );
    }
    self::$browserOutputPrepared = TRUE;
  }

  /**
   * Ensure a tournament can be created, saved, and reloaded.
   */
  public function testTournamentCreateAndLoad(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('node');

    $bracketData = [
      'stages' => [],
      'matches' => [],
    ];

    $participants = [];
    foreach (['Alpha' => 1, 'Beta' => 2, 'Gamma' => 3] as $name => $seed) {
      $participant = $storage->create([
        'type' => 'participant',
        'title' => $name,
        'field_seeding' => $seed,
      ]);
      $participant->save();
      $participants[] = $participant;
    }

    $tournament = $storage->create([
      'type' => 'tournament',
      'title' => 'Test Cup',
      'field_description' => 'Demo tournament for testing.',
      'field_bracket_data' => json_encode($bracketData),
      'field_active' => TRUE,
      'field_tournament_participants' => array_map(static fn($p) => $p->id(), $participants),
    ]);
    $tournament->save();

    $loaded = $storage->load($tournament->id());
    $this->assertNotNull($loaded);
    $this->assertSame('Test Cup', $loaded->label());
    $this->assertSame(['Alpha', 'Beta', 'Gamma'], array_map(static fn($p) => $p->label(), $loaded->get('field_tournament_participants')->referencedEntities()));
    $this->assertSame($tournament->getChangedTime(), $loaded->getChangedTime());
    $this->assertEquals($bracketData, json_decode($loaded->get('field_bracket_data')->value, TRUE));
  }

  /**
   * Ensure view builder attaches expected drupalSettings structure.
   */
  public function testViewBuilderAttachments(): void {
    $storage = $this->container->get('entity_type.manager')->getStorage('node');
    $participants = [];
    foreach (['One' => 1, 'Two' => 2] as $name => $seed) {
      $participant = $storage->create([
        'type' => 'participant',
        'title' => $name,
        'field_seeding' => $seed,
      ]);
      $participant->save();
      $participants[] = $participant;
    }
    $tournament = $storage->create([
      'type' => 'tournament',
      'title' => 'Preview Cup',
      'field_tournament_participants' => array_map(static fn($p) => $p->id(), $participants),
      'field_bracket_data' => '{"stages":[{"id":1}]}',
    ]);
    $tournament->save();

    $build = $this->container->get('entity_type.manager')->getViewBuilder('node')->view($tournament, 'full');
    $this->assertArrayHasKey('#attached', $build);
    $this->assertArrayHasKey('drupalSettings', $build['#attached']);
    $settings = $build['#attached']['drupalSettings'];

    $this->assertArrayHasKey('bracketManager', $settings);
    $this->assertArrayHasKey('tournaments', $settings['bracketManager']);
    $this->assertArrayHasKey($tournament->id(), $settings['bracketManager']['tournaments']);

    $data = $settings['bracketManager']['tournaments'][$tournament->id()];
    $this->assertSame('Preview Cup', $data['name']);
    $this->assertSame(['One', 'Two'], $data['participants']);
    $this->assertSame('{"stages":[{"id":1}]}', $data['data']);
  }

  /**
   * Ensures the participant content type and seeding field exist for tests.
   */
  protected function ensureParticipantContentType(): void {
    $type = NodeType::load('participant');
    if (!$type) {
      $type = NodeType::create([
        'type' => 'participant',
        'name' => 'Participant',
      ]);
      $type->save();
    }

    $storage = FieldStorageConfig::loadByName('node', 'field_seeding');
    if (!$storage) {
      $storage = FieldStorageConfig::create([
        'field_name' => 'field_seeding',
        'entity_type' => 'node',
        'type' => 'integer',
      ]);
      $storage->save();
    }

    $field = FieldConfig::loadByName('node', 'participant', 'field_seeding');
    if (!$field) {
      $field = FieldConfig::create([
        'field_name' => 'field_seeding',
        'entity_type' => 'node',
        'bundle' => 'participant',
        'label' => 'Seeding',
      ]);
      $field->save();
    }
  }

  /**
   * Ensures the tournament content type exists for tests.
   */
  protected function ensureTournamentContentType(): void {
    $type = NodeType::load('tournament');
    if (!$type) {
      $type = NodeType::create([
        'type' => 'tournament',
        'name' => 'Tournament',
      ]);
      $type->save();
    }

    $storage = FieldStorageConfig::loadByName('node', 'field_bracket_data');
    if (!$storage) {
      $storage = FieldStorageConfig::create([
        'field_name' => 'field_bracket_data',
        'entity_type' => 'node',
        'type' => 'text_long',
      ]);
      $storage->save();
    }
    $field = FieldConfig::loadByName('node', 'tournament', 'field_bracket_data');
    if (!$field) {
      FieldConfig::create([
        'field_name' => 'field_bracket_data',
        'entity_type' => 'node',
        'bundle' => 'tournament',
        'label' => 'Bracket data',
      ])->save();
    }

    $p_storage = FieldStorageConfig::loadByName('node', 'field_tournament_participants');
    if (!$p_storage) {
      $p_storage = FieldStorageConfig::create([
        'field_name' => 'field_tournament_participants',
        'entity_type' => 'node',
        'type' => 'entity_reference',
        'settings' => ['target_type' => 'node'],
        'cardinality' => -1,
      ]);
      $p_storage->save();
    }
    $p_field = FieldConfig::loadByName('node', 'tournament', 'field_tournament_participants');
    if (!$p_field) {
      FieldConfig::create([
        'field_name' => 'field_tournament_participants',
        'entity_type' => 'node',
        'bundle' => 'tournament',
        'label' => 'Participants',
      ])->save();
    }
  }

}
