<?php
/**
 * Article editor, rendered inside the side panel.
 *
 * @package MiniDocs
 *
 * @var MdArticleModel $article
 * @var array          $categories
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$md_is_new = $article->is_new_record();
$md_route  = $md_is_new
	? MdRouterHelper::build_route_name( 'articles', 'create' )
	: MdRouterHelper::build_route_name( 'articles', 'update' );
$md_nonce  = $md_is_new ? wp_create_nonce( 'new_article' ) : wp_create_nonce( 'edit_article_' . $article->id );
?>
<div class="md-form-w md-form-w-wide <?php echo $md_is_new ? 'md-is-new' : 'md-is-existing'; ?>">
	<form action="#" class="md-form" data-route-name="<?php echo esc_attr( $md_route ); ?>">

		<div class="md-form-header">
			<h2><?php echo $md_is_new ? esc_html__( 'New Article', 'minidocs' ) : esc_html__( 'Edit Article', 'minidocs' ); ?></h2>
			<button type="button" class="md-side-panel-close" data-md-close-panel aria-label="<?php esc_attr_e( 'Close', 'minidocs' ); ?>">
				<i class="dashicons dashicons-no-alt"></i>
			</button>
		</div>

		<div class="md-form-content">

			<?php if ( ! $md_is_new ) { ?>
				<div class="md-record-meta">
					<span><?php esc_html_e( 'ID:', 'minidocs' ); ?></span>
					<strong>#<?php echo esc_html( $article->id ); ?></strong>
					<span class="md-record-meta-sep">&middot;</span>
					<span>
						<?php
						printf(
							/* translators: %s: view count. */
							esc_html__( '%s views', 'minidocs' ),
							esc_html( number_format_i18n( (int) $article->views_count ) )
						);
						?>
					</span>
					<span class="md-record-meta-sep">&middot;</span>
					<span><?php echo esc_html( $article->formatted_updated_date() ); ?></span>

					<?php
					// Only offered once a knowledge base page has been chosen in Settings.
					$md_preview_url = MdSettingsHelper::get_article_preview_url( (string) $article->slug );

					if ( $md_preview_url && MINIDOCS_ARTICLE_STATUS_PUBLISHED === $article->status ) {
						?>
						<a class="md-record-meta-link" href="<?php echo esc_url( $md_preview_url ); ?>" target="_blank" rel="noopener">
							<i class="dashicons dashicons-external"></i><?php esc_html_e( 'View', 'minidocs' ); ?>
						</a>
						<?php
					}
					?>
				</div>
			<?php } ?>

			<?php echo MdFormHelper::hidden_field( 'article[id]', $article->id ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped ?>
			<?php echo MdFormHelper::hidden_field( '_wpnonce', $md_nonce ); // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped ?>

			<?php
			echo MdFormHelper::text_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
				'article[title]',
				__( 'Title', 'minidocs' ),
				$article->title,
				array( 'placeholder' => __( 'e.g. How to reset your password', 'minidocs' ) )
			);
			?>

			<div class="md-row">
				<div class="md-col-6">
					<?php
					echo MdFormHelper::select_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
						'article[category_id]',
						__( 'Category', 'minidocs' ),
						$categories,
						$article->category_id
					);
					?>
				</div>
				<div class="md-col-6">
					<?php
					echo MdFormHelper::select_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
						'article[status]',
						__( 'Status', 'minidocs' ),
						MdArticlesHelper::get_statuses_list(),
						$article->status ? $article->status : MINIDOCS_ARTICLE_STATUS_DRAFT,
						array( 'description' => __( 'Drafts are hidden from readers.', 'minidocs' ) )
					);
					?>
				</div>
			</div>

			<?php
			echo MdFormHelper::textarea_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
				'article[excerpt]',
				__( 'Excerpt', 'minidocs' ),
				$article->excerpt,
				array(
					'rows'        => 2,
					'description' => __( 'Shown in listings and search results. Generated from the body when left blank.', 'minidocs' ),
				)
			);
			?>

			<div class="md-tpl-row">
				<label for="md-article-template"><?php esc_html_e( 'Start from a template', 'minidocs' ); ?></label>
				<div class="md-form-group">
					<select id="md-article-template" class="md-input md-select">
						<option value=""><?php esc_html_e( 'Blank article', 'minidocs' ); ?></option>
						<?php foreach ( MdArticlesHelper::get_content_templates() as $md_tpl_slug => $md_tpl ) { ?>
							<option value="<?php echo esc_attr( $md_tpl_slug ); ?>"><?php echo esc_html( $md_tpl['label'] ); ?></option>
						<?php } ?>
					</select>
				</div>
				<span class="md-tpl-hint"><?php esc_html_e( 'Drops a professional structure into the editor for you to fill in.', 'minidocs' ); ?></span>
			</div>

			<div class="md-form-group md-editor-group">
				<label for="md-article-content"><?php esc_html_e( 'Content', 'minidocs' ); ?></label>

				<?php
				// A plain textarea, upgraded to the full WordPress TinyMCE editor by
				// minidocsInitArticleForm() once this markup is in the DOM. Pasting
				// from a .docx / Google Doc keeps its headings, bold and lists;
				// TinyMCE strips the surrounding Word markup. Images come in through
				// the Add Media button or by drag-and-drop. If the editor API is
				// unavailable the raw textarea still accepts HTML.
				?>
				<textarea id="md-article-content"
					name="article[content]"
					class="md-input md-textarea md-wp-editor"
					rows="16"><?php echo esc_textarea( (string) $article->content ); ?></textarea>

				<div class="md-form-description">
					<?php esc_html_e( 'Paste straight from a Word or Google document — formatting is cleaned up automatically. Every H2 and H3 becomes an entry in the reader-facing table of contents.', 'minidocs' ); ?>
				</div>
			</div>

			<div class="md-form-section">
				<h3><?php esc_html_e( 'Placement', 'minidocs' ); ?></h3>
			</div>

			<div class="md-row">
				<div class="md-col-6">
					<?php
					echo MdFormHelper::number_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
						'article[order_number]',
						__( 'Order', 'minidocs' ),
						(int) $article->order_number,
						0,
						null,
						array( 'description' => __( 'Lower numbers come first within the category.', 'minidocs' ) )
					);
					?>
				</div>
				<div class="md-col-6">
					<?php
					echo MdFormHelper::text_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
						'article[slug]',
						__( 'Slug', 'minidocs' ),
						$article->slug,
						array( 'description' => __( 'Derived from the title when blank.', 'minidocs' ) )
					);
					?>
				</div>
			</div>

			<?php
			echo MdFormHelper::toggle_field( // phpcs:ignore WordPress.Security.EscapingOutput.OutputNotEscaped
				'article[is_featured]',
				__( 'Feature on the knowledge base landing page', 'minidocs' ),
				'on',
				$article->is_featured()
			);
			?>

			<?php
			/**
			 * Fires inside the article editor, after the core fields.
			 *
			 * @since 1.0.0
			 * @hook minidocs_article_form_after_fields
			 *
			 * @param MdArticleModel $article Article being edited.
			 */
			do_action( 'minidocs_article_form_after_fields', $article );
			?>
		</div>

		<div class="md-form-footer">
			<button type="button" class="md-btn md-btn-outline" data-md-close-panel><?php esc_html_e( 'Cancel', 'minidocs' ); ?></button>
			<button type="submit" class="md-btn md-btn-primary">
				<?php echo $md_is_new ? esc_html__( 'Create Article', 'minidocs' ) : esc_html__( 'Save Changes', 'minidocs' ); ?>
			</button>
		</div>
	</form>
</div>
