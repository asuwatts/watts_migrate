<?php

namespace Drupal\watts_migrate;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Create an HTML Block from a node_content pane.
 */
class WattsNodeContentPaneToBlock extends WattsPaneToBlock {
  use WattsMediaWysiwygTransformTrait;
  use WattsAppendFilesTrait;
  use WattsWysiwygTextProcessingTrait;

  /**
   * The drupal_7 database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $d7Connection;

  /**
   * The entity type manager server.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a WattsPaneToBlock object.
   *
   * @param Drupal\Core\Database\Connection $database
   *   The injected database service.
   * @param Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(Connection $database, EntityTypeManager $entity_type_manager) {
    $this->d7Connection = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('watts_migrate.watts_pane_to_block'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function createBlock($row, $record, $configuration) {
    // Transform body field content.
    $body_field = $row->get('body');
    $body_field[0]['value'] = $this->transformWysiwyg($body_field[0]['value'], $this->entityTypeManager);
    $body_field[0]['value'] = $this->processText($body_field[0]['value']);

    // Append files to the body field for document node types.
    if ($row->getSourceProperty('type') === 'document') {
      $fids = $row->getSourceProperty('field_file');

      if ($fids) {
        $body_field[0]['value'] = $this->appendFiles($body_field[0]['value'], $fids, $this->entityTypeManager);
      }

    }

    return $this->createHtmlBlock($body_field, $this->entityTypeManager);
  }

}
