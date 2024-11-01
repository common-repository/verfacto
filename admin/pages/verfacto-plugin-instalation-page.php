<div class="verfacto-page-wrapper">
	<div class="verfacto-page-head">
		<div class="verfacto-head-content">
			<img style="width: 65px; height: 65px;" src="https://cdn.verfacto.com/wp-content/uploads/2022/01/31124111/verfacto-icon-65x65-1.svg" />
			<p class="verfacto-head-title">Verfacto Marketing Analytics for WooCommerce <br />
				<span>See the full picture of your marketing and get daily insights on how to improve it.</span>
			</p>
		</div>
	</div>
   <?php if ( array_key_exists( 'success', $_GET ) && trim( $_GET['success'] ) === '0' && ! $is_page_refreshed ) : ?>
   <div class="verfacto-error-message verfacto-error-danger">
	  <strong>Error</strong> - To install plugin, you need to approve access to your store.
   </div>
   <?php endif; ?>

   <?php if ( get_transient( 'verfacto_error_message' ) ) : ?>
   <div class="verfacto-error-message verfacto-error-danger">
	  <strong>Error</strong> - 
		<?php
		echo get_transient( 'verfacto_error_message' );
		delete_transient( 'verfacto_error_message' );
		?>
   </div>
   <?php endif; ?>

   <div class="verfacto-error-message verfacto-error-danger ajax" style="display: none;">
	  <strong>Error</strong> - <span class='verfacto-error-text'><span/>
   </div>
   <?php if ( ! $keys_exist || ! $plugin_active ) : ?>
   <div class="verfacto-page-content">
   		<div class="verfacto-page-content-header">
			<div class="verfacto-page-title">
				<h4>Plugin integration is pending</h4>
			</div>
			<div class="verfacto-gradient-circle"></div>
		</div>
		<div class="verfacto-form">
		<ul class="verfacto-tab-group">
			<li class="verfacto-tab" style="flex: 1 1 auto; font-size: 16px;">Please enter your account details to finish integration</li>
			<li class="verfacto-tab" style="flex: 1 1 auto;"><a href="<?php echo $go_to_create_account; ?>" target="_blank">Don’t have an account? Click here to sign up</a></li>
		</ul>
		<div class="verfacto-tab-content" style="width: 320px; padding-top: 20px;">
			<div id="verfacto-login">
				<form id="verfacto-integrate-form" action="/" method="post">
					<div class="verfacto-field-wrap">
					<label>Email</label>
					<input type="email" name="user_email" required maxlength="125"/>
					</div>
					<div class="verfacto-field-wrap">
					<label>Password</label>
					<input type="password" name="user_password" required maxlength="125" autocomplete="off"/>
					</div>
					<a class="verfacto-forget-password" href="<?php echo esc_url( $forgot_password_url ); ?>" target="_blank">Forgot your password?</a>
				<button id="submit_integration_form" type="submit" class="verfacto-button"/>Integrate</button>
				<div class="verfacto-loader"></div>
				</form>
			</div>
		</div>
		</div>
	</div>
	<?php else: ?>
	<div class="verfacto-page-content">
		<div class="verfacto-page-content-header">
			<div class="verfacto-page-title">
				<h4>Plugin is fully integrated</h4>
			</div>
			<div class="verfacto-gradient-circle"></div>
		</div>
		<div class="verfacto-page-description">
			<div class="verfacto-page-description-title">
				<h3>Important!</h3>
			</div>
			<p class="verfacto-page-description-text">
				Verfacto uses javascript for tracking user behaviour, website content
				needs to be updated after the installation, otherwise user tracking
				might not work correctly.
			</p>
			<p class="verfacto-page-description-text">
				If you use any WordPress performance optimisation plugin, you shall
				do:
			</p>
			<ol class="verfacto-page-description-list">
				<li class="verfacto-page-description-text">
					<strong>Clear the cache.</strong>
				</li>
				<li class="verfacto-page-description-text">
					<strong>Do no minify Verfacto scripts.</strong> Add
					<span style="color: #858493">https://analytics.verfacto.com/distributor.js</span> javascript file to
					ignore/exclusion list.
				</li>
			</ol>
			<p class="verfacto-page-description-text">
				Check your plugin’s documentation on certain actions.
			</p>
			<a href="<?php echo $open_verfacto ?>" target="_blank" class="verfacto-button">Open Verfacto</a>
		</div>
	</div>
	<?php endif; ?>

	<div class="verfacto-page-footer">
	<div class="verfacto-page-title">
			<h4 style="padding: 0; font-size: 18px; line-height: 25px; color: #32373D;">Having troubles?</h4>
			<ul class="verfacto-troubles-list">
				<li><a href="https://www.youtube.com/watch?v=g0zwomwuRWg" target="blank">Watch an explanatory video</a></li>
				<li><a href="mailto:support@verfacto.com" target="blank">Contact support</a></li>
				<li><a href="https://www.verfacto.com/book-demo/" target="blank">Book a demo</a></li>
			</ul>
	</div>
</div>
