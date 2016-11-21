<?php
/**
 * Created by PhpStorm.
 * User: p1ratrulezzz
 * Date: 21.11.16
 * Time: 22:46
 */

namespace Drupal\taxonomy_term_depth\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\taxonomy\VocabularyInterface;

class DepthUpdateForm extends FormBase {
  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'taxonomy_term_depth_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, VocabularyInterface $vocabulary = NULL) {
    //Ensure that we have vocabulary
    $vocabulary = \Drupal::request()->get('taxonomy_vocabulary');

    /**
     * @var \Drupal\Core\Database\Connection
     */
    $dbh = \Drupal::database();
    $countAll = $dbh->select('taxonomy_term_field_data', 'ttd')
      ->condition('ttd.vid', $vocabulary->id())
      ->countQuery()->execute()->fetchField();

    $countEmpty = $dbh->select('taxonomy_term_field_data', 'ttd')
      ->condition('ttd.vid', $vocabulary->id())
      ->condition('ttd.depth', '', 'IS NULL')
      ->countQuery()->execute()->fetchField();

    // Truncate until two digits at the end without rounding the value.
    $percentProcessed = floor((100 - (100 * $countEmpty / $countAll)) * 100) / 100;
    $form['display']['processed_info'] = [
      '#type' => 'item',
      'value' => [
        '#markup' => '
            <span class="title">Processed</span>
            <span class="value">'. $percentProcessed .'</span>
            <span class="suffix">%</span>
        ',
      ],
    ];

    $form['actions']['rebuild all'] = [
      '#identity' => 'btn_rebuild_all',
      '#value' => t('Rebuild all terms'),
      '#type' => 'submit',
    ];

    $form['vid'] = [
      '#type' => 'value',
      '#value' => $vocabulary->id(),
    ];
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $identity = isset($form_state->getTriggeringElement()['#identity']) ? $form_state->getTriggeringElement()['#identity'] : 'unknown';
    $options = array();
    $options['vids'] = $form_state->getValue('vid');
    switch ($identity) {
      case 'btn_rebuild_all':
        batch_set(array(
          'operations' => array(
            array('taxonomy_term_depth_batch_callbacks_update_term_depth', array($options)),
          ),
          'title' => t('Updating depths for all terms'),
          'file' => TAXONOMY_TERM_DEPTH_ROOT_REL. '/taxonomy_term_depth.batch.inc',
        ));
        break;
      default:
        drupal_set_message(t('Wrong operation selected'));
    }
  }
}
