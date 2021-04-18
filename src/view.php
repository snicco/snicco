<?php


	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	do_action( 'wpemerge.kernels.http_kernel.respond' );
	remove_all_filters( 'wpemerge.kernels.http_kernel.respond' );
