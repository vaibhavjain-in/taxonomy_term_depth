<?php

namespace Drupal\taxonomy_term_depth;

use Drupal\Core\Entity\FieldableEntityStorageInterface;
use Drupal\Core\Field\FieldModuleUninstallValidator;
use Drupal\Core\Url;
/**
 * Prevents uninstallation of modules providing active field storage.
 */
// class DepthUninstallValidator implements FieldModuleUninstallValidator {
class DepthUninstallValidator extends FieldModuleUninstallValidator{

  /**
   * {@inheritdoc}
   */
  public function validate($module_name) {
    $reasons = array();

    // We skip fields provided by the Field module as it implements field
    // purging.
    if ($module_name != 'field') {
      foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
        // We skip entity types defined by the module as there must be no
        // content to be able to uninstall them anyway.
        // See \Drupal\Core\Entity\ContentUninstallValidator.
        if ($entity_type->getProvider() != $module_name && $entity_type->isSubclassOf('\Drupal\Core\Entity\FieldableEntityInterface')) {
          foreach ($this->entityManager->getFieldStorageDefinitions($entity_type_id) as $storage_definition) {
            if ($storage_definition->getProvider() == $module_name) {
              $storage = $this->entityManager->getStorage($entity_type_id);
              if ($storage instanceof FieldableEntityStorageInterface && $storage->countFieldData($storage_definition, TRUE)) {
                $reasons[] = $this->t('There is data for the field @field-name on entity type @entity_type. <a href=":url">Delete depth fields data.</a>.', array(
                  '@field-name' => $storage_definition->getName(),
                  '@entity_type' => $entity_type->getLabel(),
                  ':url' => Url::fromRoute('taxonomy_term_depth.prepare_modules_uninstall')->toString(),
                ));
              }
            }
          }
        }
      }
    }

    return $reasons;
  }

}
