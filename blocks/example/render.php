<?php
/**
 * Example block render template.
 *
 * @package BlockBridge
 */

$heading = get_field( 'heading' );
$text    = get_field( 'text' );
$image   = get_field( 'image' );
?>

<section class="bb-example">

    <?php if ( $heading ) : ?>
        <h2 class="bb-example__heading"><?php echo esc_html( $heading ); ?></h2>
    <?php endif; ?>

    <?php if ( $text ) : ?>
        <p class="bb-example__text"><?php echo esc_html( $text ); ?></p>
    <?php endif; ?>

    <?php if ( $image ) : ?>
        <img
            class="bb-example__image"
            src="<?php echo esc_url( $image['url'] ); ?>"
            alt="<?php echo esc_attr( $image['alt'] ); ?>"
            width="<?php echo esc_attr( $image['width'] ); ?>"
            height="<?php echo esc_attr( $image['height'] ); ?>"
        />
    <?php endif; ?>

</section>