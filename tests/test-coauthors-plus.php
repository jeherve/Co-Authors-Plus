<?php

class Test_CoAuthors_Plus extends CoAuthorsPlus_TestCase {

	public function setUp() {

		parent::setUp();

		$this->author1 = $this->factory->user->create_and_get( array( 'role' => 'author', 'user_login' => 'author1' ) );
		$this->editor1 = $this->factory->user->create_and_get( array( 'role' => 'editor', 'user_login' => 'editor1' ) );

		$this->post = $this->factory->post->create_and_get( array(
			'post_author'  => $this->author1->ID,
			'post_status'  => 'publish',
			'post_content' => rand_str(),
			'post_title'   => rand_str(),
			'post_type'    => 'post',
		) );
	}

	/**
	 * Checks whether the guest authors functionality is enabled or not.
	 *
	 * @covers ::is_guest_authors_enabled()
	 */
	public function test_is_guest_authors_enabled() {

		global $coauthors_plus;

		$this->assertTrue( $coauthors_plus->is_guest_authors_enabled() );

		tests_add_filter( 'coauthors_guest_authors_enabled', '__return_false' );

		$this->assertFalse( $coauthors_plus->is_guest_authors_enabled() );
	}

	/**
	 * Checks coauthor object when he/she is a guest author.
	 *
	 * @covers ::get_coauthor_by()
	 */
	public function test_get_coauthor_by_when_guest_author() {

		global $coauthors_plus;

		$guest_author_id = $coauthors_plus->guest_authors->create( array(
			'user_login'   => 'author2',
			'display_name' => 'author2',
		) );

		$coauthor = $coauthors_plus->get_coauthor_by( 'id', $guest_author_id );

		$this->assertInternalType( 'object', $coauthor );
		$this->assertInstanceOf( stdClass::class, $coauthor );
		$this->assertObjectHasAttribute( 'ID', $coauthor );
		$this->assertEquals( $guest_author_id, $coauthor->ID );
		$this->assertEquals( 'guest-author', $coauthor->type );
	}

	/**
	 * Checks coauthor object when he/she is a wp author.
	 *
	 * @covers ::get_coauthor_by()
	 */
	public function test_get_coauthor_by_when_wp_user() {

		global $coauthors_plus;

		$this->assertFalse( $coauthors_plus->get_coauthor_by( '', '' ) );

		$coauthor = $coauthors_plus->get_coauthor_by( 'id', $this->author1->ID );

		$this->assertInternalType( 'object', $coauthor );
		$this->assertInstanceOf( WP_User::class, $coauthor );
		$this->assertObjectHasAttribute( 'ID', $coauthor );
		$this->assertEquals( $this->author1->ID, $coauthor->ID );
		$this->assertEquals( 'wpuser', $coauthor->type );

		$coauthor = $coauthors_plus->get_coauthor_by( 'user_login', $this->author1->user_login );

		$this->assertInternalType( 'object', $coauthor );
		$this->assertInstanceOf( WP_User::class, $coauthor );
		$this->assertObjectHasAttribute( 'user_login', $coauthor->data );
		$this->assertEquals( $this->author1->user_login, $coauthor->user_login );

		$coauthor = $coauthors_plus->get_coauthor_by( 'user_nicename', $this->author1->user_nicename );

		$this->assertInternalType( 'object', $coauthor );
		$this->assertInstanceOf( WP_User::class, $coauthor );
		$this->assertObjectHasAttribute( 'user_nicename', $coauthor->data );
		$this->assertEquals( $this->author1->user_nicename, $coauthor->user_nicename );

		$coauthor = $coauthors_plus->get_coauthor_by( 'user_email', $this->author1->user_email );

		$this->assertInternalType( 'object', $coauthor );
		$this->assertInstanceOf( WP_User::class, $coauthor );
		$this->assertObjectHasAttribute( 'user_email', $coauthor->data );
		$this->assertEquals( $this->author1->user_email, $coauthor->user_email );

		$coauthors_plus->guest_authors->create_guest_author_from_user_id( $this->editor1->ID );

		$coauthor = $coauthors_plus->get_coauthor_by( 'id', $this->editor1->ID );

		$this->assertInternalType( 'object', $coauthor );
		$this->assertInstanceOf( stdClass::class, $coauthor );
		$this->assertObjectHasAttribute( 'linked_account', $coauthor );
		$this->assertEquals( $this->editor1->user_login, $coauthor->linked_account );
	}

	/**
	 * Checks coauthors plus is enabled for this post type.
	 *
	 * @covers ::is_post_type_enabled()
	 */
	public function test_is_post_type_enabled() {

		global $coauthors_plus, $post;

		// Backing up global post.
		$post_backup = $post;

		// Checks when post type is null.
		$this->assertFalse( $coauthors_plus->is_post_type_enabled() );

		// Checks when post type is post.
		$this->assertTrue( $coauthors_plus->is_post_type_enabled( 'post' ) );

		// Checks when post type is page.
		$this->assertTrue( $coauthors_plus->is_post_type_enabled( 'page' ) );

		// Checks when post type is attachment.
		$this->assertFalse( $coauthors_plus->is_post_type_enabled( 'attachment' ) );

		// Checks when post type is revision.
		$this->assertFalse( $coauthors_plus->is_post_type_enabled( 'revision' ) );

		$post = $this->post;

		// Checks when post type set using global post.
		$this->assertTrue( $coauthors_plus->is_post_type_enabled() );

		$post   = '';
		$screen = get_current_screen();

		// Set the edit post current screen.
		set_current_screen( 'edit-post' );
		$this->assertTrue( $coauthors_plus->is_post_type_enabled() );

		$GLOBALS['current_screen'] = $screen;

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks if the current user can set co-authors or not using current screen.
	 *
	 * @covers ::current_user_can_set_authors()
	 */
	public function test_current_user_can_set_authors_using_current_screen() {

		global $coauthors_plus;

		$this->assertFalse( $coauthors_plus->current_user_can_set_authors() );
	}

	/**
	 * Checks if the current user can set co-authors or not using global post.
	 *
	 * @covers ::current_user_can_set_authors()
	 */
	public function test_current_user_can_set_authors_using_global_post() {

		global $coauthors_plus, $post;

		// Backing up global post.
		$post_backup = $post;

		$post = $this->post;

		$this->assertFalse( $coauthors_plus->current_user_can_set_authors() );

		// Backing up current user.
		$current_user = get_current_user_id();

		// Checks when current user is author.
		wp_set_current_user( $this->author1->ID );

		$this->assertFalse( $coauthors_plus->current_user_can_set_authors() );

		// Checks when current user is editor.
		wp_set_current_user( $this->editor1->ID );

		$this->assertTrue( $coauthors_plus->current_user_can_set_authors() );

		// Checks when current user is super admin.
		$admin1 = $this->factory->user->create_and_get( array(
			'role' => 'administrator',
		) );

		grant_super_admin( $admin1->ID );
		wp_set_current_user( $admin1->ID );

		$this->assertTrue( $coauthors_plus->current_user_can_set_authors() );

		// Restore current user from backup.
		wp_set_current_user( $current_user );

		// Restore global post from backup.
		$post = $post_backup;
	}

	/**
	 * Checks if the current user can set co-authors or not using normal post.
	 *
	 * @covers ::current_user_can_set_authors()
	 */
	public function test_current_user_can_set_authors_using_normal_post() {

		global $coauthors_plus;

		$this->assertFalse( $coauthors_plus->current_user_can_set_authors( $this->post ) );

		// Backing up current user.
		$current_user = get_current_user_id();

		// Checks when current user is author.
		wp_set_current_user( $this->author1->ID );

		$this->assertFalse( $coauthors_plus->current_user_can_set_authors( $this->post ) );

		// Checks when current user is editor.
		wp_set_current_user( $this->editor1->ID );

		$this->assertTrue( $coauthors_plus->current_user_can_set_authors( $this->post ) );

		// Checks when current user is super admin.
		$admin1 = $this->factory->user->create_and_get( array(
			'role' => 'administrator',
		) );

		grant_super_admin( $admin1->ID );
		wp_set_current_user( $admin1->ID );

		$this->assertTrue( $coauthors_plus->current_user_can_set_authors( $this->post ) );

		// Restore current user from backup.
		wp_set_current_user( $current_user );
	}
}
