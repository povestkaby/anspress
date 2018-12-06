<?php
/**
 * Display single question category page.
 *
 * Display category page.
 *
 * @link        http://anspress.io
 * @since       4.2.0
 * @package     AnsPress
 * @subpackage  Templates
 */

namespace AnsPress;

$category  = get_queried_object();
$order_by  = get_current_questions_sorting();
$filter_by = get_current_questions_filter();

// Query args.
$question_args = array(
	'ap_order_by'  => $order_by,
	'ap_filter_by' => $filter_by,
	'tax_query'    => array(
		array(
			'taxonomy' => 'question_category',
			'field'    => 'id',
			'terms'    => array( get_queried_object_id() ),
		),
	),
	'pagination_base' => get_term_link( $category, 'question_category' ),
);

$icon = ap_get_category_icon( $category->term_id );
?>

<?php dynamic_sidebar( 'ap-top' ); ?>

<div id="ap-category">

	<?php if ( ap_category_have_image( $category->term_id ) ) : ?>
		<div class="ap-category-feat" style="height: 300px;">
			<?php ap_category_image( $category->term_id, 300 ); ?>
		</div>
	<?php endif; ?>

	<div class="ap-taxo-detail">
		<?php if ( ! empty( $icon ) ) : ?>
			<div class="ap-pull-left">
				<?php ap_category_icon( $category->term_id ); ?>
			</div>
		<?php endif; ?>

		<div class="no-overflow">
			<div>
				<span class="ap-tax-count">
				<?php
					printf(
						_n( '%d Question', '%d Questions', (int) $category->count, 'anspress-question-answer' ),
						(int) $category->count
					);
				?>
				</span>
			</div>


			<?php if ( '' !== $category->description ) : ?>
				<p class="ap-taxo-description">
					<?php echo wp_kses_post( $category->description ); ?>
				</p>
			<?php endif; ?>

			<?php
				$sub_cat_count = count( get_term_children( $category->term_id, 'question_category' ) );

			if ( $sub_cat_count > 0 ) {
				echo '<div class="ap-term-sub">';
				echo '<div class="sub-taxo-label">' . $sub_cat_count . ' ' . __( 'Sub Categories', 'anspress-question-answer' ) . '</div>';
				ap_sub_category_list( $category->term_id );
				echo '</div>';
			}
			?>
		</div>
	</div><!-- close .ap-taxo-detail -->

	<div class="ap-flex-row<?php echo is_active_sidebar( 'anspress-category' ) ? ' ap-sidebar-active' : ''; ?>">
		<div class="ap-col-main">
			<?php ap_get_template_part( 'questions-sort-filters' ); ?>

			<?php if ( ap_get_questions( $question_args ) ) : ?>
				<?php ap_get_template_part( 'loop-questions' ); ?>
				<?php ap_get_template_part( 'pagination-questions' ); ?>
			<?php else : ?>
				<?php ap_get_template_part( 'feedback-questions' ); ?>
				<?php ap_get_template_part( 'login-signup' ); ?>
			<?php endif; ?>
		</div>

		<?php if ( is_active_sidebar( 'anspress-category' ) ) : ?>
			<div class="ap-col-sidebar ap-sidebar-category">
				<?php dynamic_sidebar( 'anspress-category' ); ?>
			</div>
		<?php endif; ?>

	</div>

</div>