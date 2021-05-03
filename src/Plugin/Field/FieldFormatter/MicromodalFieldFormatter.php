<?php

namespace Drupal\media_video_micromodal\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\image\Entity\ImageStyle;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\media\IFrameUrlHelper;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\PrivateKey;

/**
 * Plugin implementation of the 'micromodal_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "micromodal_field_formatter",
 *   label = @Translation("Micromodal field formatter"),
 *   field_types = {
 *     "image"
 *   }
 * )
 */
class MicromodalFieldFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
      'thumbnail_image_style' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
      'thumbnail_image_style' => [
        '#title' => t('Video Thumbnail Image Style'),
        '#type' => 'select',
        '#options' => image_style_options(FALSE),
        '#empty_option' => '<' . t('no preview') . '>',
        '#default_value' => $this->getSetting('thumbnail_image_style'),
        '#description' => t('Thumbnail for the video, click the thumbnail for the modal window.'),
        '#weight' => 14,
      ],
    ] + parent::settingsForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    // Implement settings summary.
    if (!empty($this->getSetting('thumbnail_image_style'))) {
      $summary[] = 'Image Style: ' . $this->getSetting('thumbnail_image_style');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {

      // Load the media item.
      $media_id = $item->getValue()['target_id'];
      $media = Media::load($media_id);

      // Grab the remote video URL.
      $video_url = $media->getFields()['field_media_oembed_video']->getValue()[0]['value'];

      // Use these to generate the URL for local oembed iframe.
      $request = new RequestContext($video_url);
      $private_key = new PrivateKey(\Drupal::state());
      $url_helper = new IFrameUrlHelper($request, $private_key);

      // These are needed to create the hash successfully.
      $max_width = 0;
      $max_height = 0;

      // Use parts above to generate the iframe url.
      $media_oembed_url = Url::fromRoute('media.oembed_iframe', [], [
        'query' => [
          'url' => $video_url,
          'max_width' => 0,
          'max_height' => 0,
          'hash' => $url_helper->getHash($video_url, $max_width, $max_height),
        ],
      ])->toString();

      // Media ID of the thumbnail.
      $thumbnail_id = $media->getFields()['thumbnail']->getValue()[0]['target_id'];

      // Initialize a default value for the thumbnail.
      $thumbnail_url = '';

      // Use the image style setting to style the thumbnail.
      $style = ImageStyle::load($this->getSetting('thumbnail_image_style'));
      if (!empty($thumbnail_id)) {
        $thumbnail_file = File::load($thumbnail_id);
        $thumbnail_url = $style->buildUrl($thumbnail_file->uri->value);
      }

      // This will be used as the value of the div.
      $modal_id = 'modal-media-' . $media_id;

      // Send these to the twig template.
      $elements[$delta] = [
        '#theme' => 'media_video_micromodal',
        '#modal_id' => $modal_id,
        '#thumbnail_url' => $thumbnail_url,
        '#iframe_src' => $media_oembed_url,
      ];

    }

    if (!empty($elements)) {
      // Attach libraries.
      $elements['#attached'] = [
        'library' => [
          'media_video_micromodal/micromodal_libraries',
        ],
      ];
    }

    return $elements;

  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition): bool {
    return $field_definition->getTargetEntityTypeId() === 'media'
      // && $field_definition->getTargetBundle() === 'video'
      && $field_definition->getFieldStorageDefinition()->getName() === 'thumbnail';
  }

}
