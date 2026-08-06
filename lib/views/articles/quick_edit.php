<?php
/**
 * Article editor, rendered inside the side panel.
 *
 * @package KnowlioDocs
 *
 * @var KnowlioArticleModel $article
 * @var array          $categories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$knowlio_is_new = $article->is_new_record();
$knowlio_route  = $knowlio_is_new
	? KnowlioRouterHelper::build_route_name( 'articles', 'create' )
	: KnowlioRouterHelper::build_route_name( 'articles', 'update' );
$knowlio_nonce  = $knowlio_is_new ? wp_create_nonce( 'new_article' ) : wp_create_nonce( 'edit_article_' . $article->id );
?>
<div class="knowlio-form-w knowlio-form-w-wide <?php echo $knowlio_is_new ? 'knowlio-is-new' : 'knowlio-is-existing'; ?>">
	<form action="#" class="knowlio-form" data-route-name="<?php echo esc_attr( $knowlio_route ); ?>">

		<div class="knowlio-form-header">
			<h2><?php echo $knowlio_is_new ? esc_html__( 'New Article', 'minidocs' ) : esc_html__( 'Edit Article', 'minidocs' ); ?></h2>
			<button type="button" class="knowlio-side-panel-close" data-knowlio-close-panel aria-label="<?php esc_attr_e( 'Close', 'minidocs' ); ?>">
				<i class="dashicons dashicons-no-alt"></i>
			</button>
		</div>

		<div class="knowlio-form-content">

			<?php if ( ! $knowlio_is_new ) { ?>
				<div class="knowlio-record-meta">
					<span><?php esc_html_e( 'ID:', 'minidocs' ); ?></span>
					<strong>#<?php echo esc_html( $article->id ); ?></strong>
					<span class="knowlio-record-meta-sep">&middot;</span>
					<span>
						<?php
						printf(
							/* translators: %s: view count. */
							esc_html__( '%s views', 'minidocs' ),
							esc_html( number_format_i18n( (int) $article->views_count ) )
						);
						?>
					</span>
					<span class="knowlio-record-meta-sep">&middot;</span>
					<span><?php echo esc_html( $article->formatted_updated_date() ); ?></span>

					<?php
					// Only offered once a knowledge base page has been chosen in Settings.
					$knowlio_preview_url = KnowlioSettingsHelper::get_article_preview_url( (string) $article->slug );

					if ( $knowlio_preview_url && KNOWLIO_ARTICLE_STATUS_PUBLISHED === $article->status ) {
						?>
						<a class="knowlio-record-meta-link" href="<?php echo esc_url( $knowlio_preview_url ); ?>" target="_blank" rel="noopener">
							<i class="dashicons dashicons-external"></i><?php esc_html_e( 'View', 'minidocs' ); ?>
						</a>
						<?php
					}
					?>
				</div>
			<?php } ?>

			<?php echo wp_kses( KnowlioFormHelper::hidden_field( 'article[id]', $article->id  ), KnowlioFormHelper::allowed_html() ); ?>
			<?php echo wp_kses( KnowlioFormHelper::hidden_field( '_wpnonce', $knowlio_nonce  ), KnowlioFormHelper::allowed_html() ); ?>

			<?php
			echo wp_kses(
				KnowlioFormHelper::text_field(
					'article[title]',
					__( 'Title', 'minidocs' ),
					$article->title,
					array( 'placeholder' => __( 'e.g. How to reset your password', 'minidocs' ) )
				),
				KnowlioFormHelper::allowed_html()
			);
			?>

			<div class="knowlio-row">
				<div class="knowlio-col-6">
					<?php
					echo wp_kses(
						KnowlioFormHelper::select_field(
							'article[category_id]',
							__( 'Category', 'minidocs' ),
							$categories,
							$article->category_id
						),
						KnowlioFormHelper::allowed_html()
					);
					?>
				</div>
				<div class="knowlio-col-6">
					<?php
					echo wp_kses(
						KnowlioFormHelper::select_field(
							'article[status]',
							__( 'Status', 'minidocs' ),
							KnowlioArticlesHelper::get_statuses_list(),
							$article->status ? $article->status : KNOWLIO_ARTICLE_STATUS_DRAFT,
							array( 'description' => __( 'Drafts are hidden from readers.', 'minidocs' ) )
						),
						KnowlioFormHelper::allowed_html()
					);
					?>
				</div>
			</div>

			<?php
			echo wp_kses(
				KnowlioFormHelper::textarea_field(
					'article[excerpt]',
					__( 'Excerpt', 'minidocs' ),
					$article->excerpt,
					array(
						'rows'        => 2,
						'description' => __( 'Shown in listings and search results. Generated from the body when left blank.', 'minidocs' ),
					)
				),
				KnowlioFormHelper::allowed_html()
			);
			?>

			<div class="knowlio-tpl-row">
				<label for="knowlio-article-template"><?php esc_html_e( 'Start from a template', 'minidocs' ); ?></label>
				<div class="knowlio-form-group">
					<select id="knowlio-article-template" class="knowlio-input knowlio-select">
						<option value=""><?php esc_html_e( 'Blank article', 'minidocs' ); ?></option>
						<?php foreach ( KnowlioArticlesHelper::get_content_templates() as $knowlio_tpl_slug => $knowlio_tpl ) { ?>
							<option value="<?php echo esc_attr( $knowlio_tpl_slug ); ?>"><?php echo esc_html( $knowlio_tpl['label'] ); ?></option>
						<?php } ?>
					</select>
				</div>
				<span class="knowlio-tpl-hint"><?php esc_html_e( 'Drops a professional structure into the editor for you to fill in.', 'minidocs' ); ?></span>
			</div>

			<div class="knowlio-form-group knowlio-editor-group">
				<label for="knowlio-article-content"><?php esc_html_e( 'Content', 'minidocs' ); ?></label>

				<?php
				// A plain textarea, upgraded to the full WordPress TinyMCE editor by
				// knowlioInitArticleForm() once this markup is in the DOM. Pasting
				// from a .docx / Google Doc keeps its headings, bold and lists;
				// TinyMCE strips the surrounding Word markup. Images come in through
				// the Add Media button or by drag-and-drop. If the editor API is
				// unavailable the raw textarea still accepts HTML.
				?>
				<textarea id="knowlio-article-content"
					name="article[content]"
					class="knowlio-input knowlio-textarea knowlio-wp-editor"
					rows="16"><?php echo esc_textarea( (string) $article->content ); ?></textarea>

				<div class="knowlio-form-description">
					<?php esc_html_e( 'Paste straight from a Word or Google document — formatting is cleaned up automatically. Every H2 and H3 becomes an entry in the reader-facing table of contents.', 'minidocs' ); ?>
				</div>
			</div>

			<div class="knowlio-form-section">
				<h3><?php esc_html_e( 'Placement', 'minidocs' ); ?></h3>
			</div>

			<div class="knowlio-row">
				<div class="knowlio-col-6">
					<?php
					echo wp_kses(
						KnowlioFormHelper::number_field(
							'article[order_number]',
							__( 'Order', 'minidocs' ),
							(int) $article->order_number,
							0,
							null,
							array( 'description' => __( 'Lower numbers come first within the category.', 'minidocs' ) )
						),
						KnowlioFormHelper::allowed_html()
					);
					?>
				</div>
				<div class="knowlio-col-6">
					<?php
					echo wp_kses(
						KnowlioFormHelper::text_field(
							'article[slug]',
							__( 'Slug', 'minidocs' ),
							$article->slug,
							array( 'description' => __( 'Derived from the title when blank.', 'minidocs' ) )
						),
						KnowlioFormHelper::allowed_html()
					);
					?>
				</div>
			</div>

			<?php
			echo wp_kses(
				KnowlioFormHelper::toggle_field(
					'article[is_featured]',
					__( 'Feature on the knowledge base landing page', 'minidocs' ),
					'on',
					$article->is_featured()
				),
				KnowlioFormHelper::allowed_html()
			);
			?>

			<?php
			/**
			 * Fires inside the article editor, after the core fields.
			 *
			 * @since 1.0.0
			 * @hook knowlio_article_form_after_fields
			 *
			 * @param KnowlioArticleModel $article Article being edited.
			 */
			do_action( 'knowlio_article_form_after_fields', $article );
			?>
		</div>

		<div class="knowlio-form-footer">
			<button type="button" class="knowlio-btn knowlio-btn-outline" data-knowlio-close-panel><?php esc_html_e( 'Cancel', 'minidocs' ); ?></button>
			<button type="submit" class="knowlio-btn knowlio-btn-primary">
				<?php echo $knowlio_is_new ? esc_html__( 'Create Article', 'minidocs' ) : esc_html__( 'Save Changes', 'minidocs' ); ?>
			</button>
		</div>
	</form>
</div>
