<?php
declare( strict_types = 1 );

namespace IPFO\DemoData;

use IPFO\Capabilities;
use IPFO\CPT\BusinessCPT;
use IPFO\CPT\CountryGuideCPT;
use IPFO\CPT\FaqCPT;
use IPFO\CPT\GuideChapterCPT;
use IPFO\CPT\ResourceCPT;
use IPFO\Repositories\ChecklistRepository;
use IPFO\Repositories\CountryRepository;
use IPFO\Repositories\InvitationRepository;
use IPFO\Services\AccessControlService;
use IPFO\Services\InvitationService;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * One-click sample content so a site owner can click through every feature
 * of the portal (and the Business Directory) without first having to invent
 * their own test data. Entirely optional, triggered only from the Demo Data
 * admin screen — never runs automatically on activation — and fully
 * reversible: every record it creates is tracked in the
 * ipfo_demo_data_refs option so remove() can delete exactly what install()
 * created, nothing more.
 *
 * The Denmark guide content below is taken directly from the "Danish Legal
 * Guide to Surrogacy Abroad" PDF the site owner supplied; the two directory
 * listings use real details pulled from the businesses' own public
 * websites. Both listings are given "Denmark" as their served country
 * purely so the demo data is internally consistent enough to click through
 * the directory's country filter — it is not a claim about where either
 * business actually practises.
 */
final class DemoDataSeeder {

	public const OPTION_INSTALLED = 'ipfo_demo_data_installed';
	public const OPTION_REFS      = 'ipfo_demo_data_refs';

	private const CHAPTERS = [
		[
			'Legal Groundwork for Danish Intended Parents',
			"<p>Denmark has recently made significant changes in this area. Previously, Danish intended parents faced a legal gap: the surrogate was treated as the legal mother regardless of genetics, and recognition at home could only be achieved through stepparent adoption, which could not begin until three months after the birth — leaving families in prolonged uncertainty.</p>\n<p>Following a political agreement and a change to the Children's Act in force from 1 January 2025, Danish intended parents can now apply for legal parenthood of a child born through surrogacy abroad, with parenthood recognised retroactively from birth and without needing to go through adoption. This applies regardless of whether you are a heterosexual couple, a same-sex couple, or a single intended parent, provided the core conditions are met: at least one intended parent must have a genetic link to the child, and the surrogate must give notarised consent to transfer parenthood.</p>\n<p>The relevant authority is Familieretshuset (the Danish Family Law Agency), and an application can generally be made before your child arrives in Denmark — which is a meaningful change from the old system. We strongly recommend instructing a Danish-based lawyer with direct experience of international surrogacy before your journey begins, alongside independent legal advice in your destination country. We're happy to introduce you to specialist contacts we've worked with previously — please ask your coordinator.</p>",
		],
		[
			'Before You Sign: Choosing Your Agency and Clinic',
			"<p>Wherever in the world you pursue surrogacy, the agency and clinic you work with will shape everything that follows — including how smoothly you're able to bring your child home. Before signing anything, it's worth checking for the following:</p>\n<ul>\n<li><strong>Licensing and accreditation:</strong> ask for documentary proof that the clinic holds a valid medical licence in its home country, and that the agency is properly registered to operate there.</li>\n<li><strong>Track record and references:</strong> ask how long the agency and clinic have operated, how many births they have supported, and whether you can speak to previous intended parents.</li>\n<li><strong>A clear, itemised written contract:</strong> in a language you're fluent in, setting out the total cost, exactly what is included, and what is not.</li>\n<li><strong>Surrogate welfare and independent consent:</strong> confirmation that the surrogate has her own independent legal representation, has given fully informed consent, and receives appropriate medical and psychological care throughout.</li>\n<li><strong>The legal parentage pathway, confirmed in writing:</strong> a written explanation of exactly how legal parentage and exit documentation will be handled — and independent confirmation from your own lawyer that this matches how the law actually works.</li>\n<li><strong>A written contingency policy:</strong> what happens, in writing, if a transfer fails, a surrogate withdraws, or a pregnancy doesn't proceed.</li>\n<li><strong>Data protection and confidentiality:</strong> how your personal and medical information, and the surrogate's, will be stored, used and protected.</li>\n</ul>\n<p>A reputable agency and clinic should be willing to put all of this in writing without hesitation. Hesitancy, vague answers, or pressure to sign quickly are worth treating as warning signs.</p>",
		],
		[
			'The Process, Step by Step',
			"<p>While each case is different, the general path for Danish intended parents now looks like this:</p>\n<ol>\n<li><strong>Genetic testing:</strong> confirming the genetic link between your child and at least one intended parent, supported by a clinic letter confirming whose gametes were used.</li>\n<li><strong>Surrogate's notarised consent:</strong> the surrogate provides formal, notarised consent to transfer legal parenthood to the intended parent(s).</li>\n<li><strong>Application to Familieretshuset:</strong> the intended parent(s) apply to the Danish Family Law Agency for legal parenthood, generally submitting the genetic test results, the surrogate's notarised consent, the local birth certificate, and the surrogacy agreement. This can generally begin before your child travels to Denmark.</li>\n<li><strong>Parenthood recognised from birth:</strong> once approved, legal parenthood is recognised retroactively from the date of birth, without the need for adoption.</li>\n<li><strong>Citizenship and passport:</strong> with legal parenthood confirmed, your child's Danish citizenship and passport can be arranged through Borgerservice.</li>\n</ol>\n<p>Because this framework is still new, requirements and processing times may continue to be refined — always confirm the current process directly with Familieretshuset and your lawyer.</p>",
		],
		[
			'Documents Checklist',
			"<p>Bringing your child home to Denmark — and having your legal parenthood recognised once there — depends on your agency and clinic providing complete, accurate paperwork at every stage. At a minimum, ask your agency and clinic to confirm in advance that they will provide:</p>\n<ul>\n<li>A clinic letter or certificate confirming the fertility treatment provided and whose gametes were used.</li>\n<li>Embryology and laboratory records tracing the embryo back to its source.</li>\n<li>The signed and dated surrogacy agreement.</li>\n<li>The surrogate's notarised consent — both to the arrangement itself and to the transfer of legal parentage.</li>\n<li>The surrogate's relevant medical records for the pregnancy and birth.</li>\n<li>The hospital's birth notification or discharge summary.</li>\n<li>The original, locally issued birth certificate.</li>\n<li>Any local court order, administrative decision or notarial deed relevant to parentage in the country of birth.</li>\n<li>Contact details for a local, independent lawyer who can confirm these documents meet what your home country will require.</li>\n</ul>\n<p>Keep certified copies of everything, and ask early — documents are far easier to obtain while you're still working with the clinic than after you've left the country.</p>",
		],
		[
			"We're Here to Help",
			"<p>We know that pursuing surrogacy abroad brings up practical questions and, understandably, a lot of feeling. Every family's situation is different — whether you are a heterosexual couple, a same-sex couple, or a single intended parent — and the information above is intended as a starting point rather than a complete answer to your circumstances. Please reach out to our team directly — we're glad to talk you through Danish legal requirements, answer your questions, and put you in touch with a Danish-based lawyer experienced in international surrogacy.</p>",
		],
	];

	private const FAQS = [
		[
			'Is surrogacy itself legal in Denmark?',
			'Commercial surrogacy is not legal in Denmark, which is why Danish intended parents pursue surrogacy abroad. Since a change to the Children\'s Act in force from 1 January 2025, Danish intended parents can apply for legal parenthood of a child born through surrogacy abroad, with parenthood recognised retroactively from birth and without needing to go through adoption.',
		],
		[
			'Which authority recognises legal parenthood in Denmark?',
			'Familieretshuset, the Danish Family Law Agency, is the relevant authority. An application can generally be made before your child arrives in Denmark, provided at least one intended parent has a genetic link to the child and the surrogate has given notarised consent.',
		],
	];

	private const CHECKLIST_ITEMS = [
		[ 'Clinic letter confirming treatment & gametes used', 'Confirms the fertility treatment provided and whose gametes (eggs, sperm or both) were used.' ],
		[ 'Embryology & laboratory records', 'Traces the embryo back to its source, confirming no donor material was substituted without your knowledge.' ],
		[ 'Signed and dated surrogacy agreement', '' ],
		[ "Surrogate's notarised consent", 'Consent to the arrangement itself and, where relevant, to the transfer of legal parentage.' ],
		[ 'Original, locally issued birth certificate', '' ],
	];

	public function is_installed(): bool {
		return (bool) get_option( self::OPTION_INSTALLED );
	}

	/**
	 * @return array{already_installed:bool, errors:string[], client_login:array, business_login:array, invitation_links:array, directory_url:string}
	 */
	public function install(): array {
		if ( $this->is_installed() ) {
			return [
				'already_installed' => true,
				'errors'            => [],
				'client_login'      => [],
				'business_login'    => [],
				'invitation_links'  => [],
				'directory_url'     => ipfo_get_portal_page_url( 'directory' ),
			];
		}

		$refs   = [ 'posts' => [], 'attachments' => [], 'terms' => [], 'users' => [], 'countries' => [], 'checklist_items' => [], 'invitations' => [] ];
		$errors = [];

		// 1. Country.
		$country_repo = new CountryRepository();
		$country_id   = $country_repo->create(
			[
				'name'          => 'Denmark',
				'code'          => 'DK',
				'flag'          => '🇩🇰',
				'description'   => 'Danish intended parents pursuing surrogacy abroad, with legal parenthood recognised under the Children\'s Act reform in force from 1 January 2025.',
				'status'        => 'active',
				'last_reviewed' => gmdate( 'Y-m-d' ),
			]
		);
		$refs['countries'][] = $country_id;

		// 2. Country Guide + chapters.
		$guide_id = wp_insert_post(
			[
				'post_type'    => CountryGuideCPT::SLUG,
				'post_title'   => 'Danish Legal Guide to Surrogacy Abroad',
				'post_content' => '<p>A guide for Danish intended parents pursuing surrogacy abroad, covering the current legal pathway to parenthood, what to check before signing with an agency or clinic, the process step by step, and the documents you will need.</p>',
				'post_status'  => 'publish',
			],
			true
		);
		if ( is_wp_error( $guide_id ) ) {
			$errors[] = 'Guide: ' . $guide_id->get_error_message();
			$guide_id = 0;
		} else {
			$refs['posts'][] = $guide_id;
			update_post_meta( $guide_id, '_ipfo_country_id', $country_id );
			update_post_meta( $guide_id, '_ipfo_version', '1.0' );
			update_post_meta( $guide_id, '_ipfo_publication_date', gmdate( 'Y-m-d' ) );
			update_post_meta( $guide_id, '_ipfo_last_reviewed', gmdate( 'Y-m-d' ) );
			update_post_meta( $guide_id, '_ipfo_online_viewing_enabled', '1' );
			update_post_meta( $guide_id, '_ipfo_ack_required', '1' );

			foreach ( self::CHAPTERS as $order => [ $title, $content ] ) {
				$chapter_id = wp_insert_post(
					[
						'post_type'    => GuideChapterCPT::SLUG,
						'post_title'   => $title,
						'post_content' => $content,
						'post_status'  => 'publish',
						'menu_order'   => $order,
					],
					true
				);
				if ( is_wp_error( $chapter_id ) ) {
					$errors[] = 'Chapter "' . $title . '": ' . $chapter_id->get_error_message();
					continue;
				}
				$refs['posts'][] = $chapter_id;
				update_post_meta( $chapter_id, '_ipfo_guide_id', $guide_id );
			}
		}

		// 3. Bundled PDF as a downloadable Resource, linked back to the guide.
		$attachment_id = $this->sideload_bundled_pdf();
		if ( is_wp_error( $attachment_id ) ) {
			$errors[] = 'PDF: ' . $attachment_id->get_error_message();
		} else {
			$refs['attachments'][] = $attachment_id;

			$resource_id = wp_insert_post(
				[
					'post_type'    => ResourceCPT::SLUG,
					'post_title'   => 'Danish Legal Guide to Surrogacy Abroad (PDF)',
					'post_content' => 'Downloadable PDF version of the Danish legal guide, for clients who want an offline copy.',
					'post_status'  => 'publish',
				],
				true
			);
			if ( is_wp_error( $resource_id ) ) {
				$errors[] = 'Resource: ' . $resource_id->get_error_message();
			} else {
				$refs['posts'][] = $resource_id;
				update_post_meta( $resource_id, '_ipfo_resource_type', 'file' );
				update_post_meta( $resource_id, '_ipfo_attachment_id', $attachment_id );
				update_post_meta( $resource_id, '_ipfo_country_id', $country_id );
				update_post_meta( $resource_id, '_ipfo_guide_id', $guide_id ?: '' );
				update_post_meta( $resource_id, '_ipfo_category', 'Legal Guides' );
				update_post_meta( $resource_id, '_ipfo_view_enabled', '1' );
				update_post_meta( $resource_id, '_ipfo_download_enabled', '1' );

				if ( $guide_id ) {
					update_post_meta( $guide_id, '_ipfo_pdf_resource_id', $resource_id );
					update_post_meta( $guide_id, '_ipfo_download_enabled', '1' );
				}
			}
		}

		// 4. FAQs.
		foreach ( self::FAQS as $order => [ $question, $answer ] ) {
			$faq_id = wp_insert_post(
				[
					'post_type'    => FaqCPT::SLUG,
					'post_title'   => $question,
					'post_content' => $answer,
					'post_status'  => 'publish',
					'menu_order'   => $order,
				],
				true
			);
			if ( is_wp_error( $faq_id ) ) {
				$errors[] = 'FAQ: ' . $faq_id->get_error_message();
				continue;
			}
			$refs['posts'][] = $faq_id;
			update_post_meta( $faq_id, '_ipfo_country_id', $country_id );
		}

		// 5. Checklist items.
		$checklist_repo = new ChecklistRepository();
		foreach ( self::CHECKLIST_ITEMS as $order => [ $label, $description ] ) {
			$item_id                    = $checklist_repo->create_item( $country_id, $label, $description, $order );
			$refs['checklist_items'][] = $item_id;
		}

		// 6. Business categories (the existing default set already has
		// "Fertility Lawyers"; "Fertility Clinics" is added to match the
		// category named for Ceres Fertility).
		$lawyer_term  = get_term_by( 'name', 'Fertility Lawyers', BusinessCPT::TAXONOMY );
		$clinic_term  = get_term_by( 'name', 'Fertility Clinics', BusinessCPT::TAXONOMY );
		if ( ! $clinic_term ) {
			$inserted = wp_insert_term( 'Fertility Clinics', BusinessCPT::TAXONOMY );
			if ( ! is_wp_error( $inserted ) ) {
				$refs['terms'][] = $inserted['term_id'];
				$clinic_term     = get_term( $inserted['term_id'], BusinessCPT::TAXONOMY );
			}
		}

		// 7. Demo business-owner account + listing 1 (Levent Kızılduman & Associates).
		$business_login = [];
		$lawyer_user_id = $this->create_demo_user( 'demo-business-owner', 'demo.lawyer@ipfo-demo.test', Capabilities::BUSINESS_ROLE, 'Levent', 'Kızılduman' );
		if ( is_wp_error( $lawyer_user_id ) ) {
			$errors[] = 'Business owner account: ' . $lawyer_user_id->get_error_message();
		} else {
			$refs['users'][]       = $lawyer_user_id['id'];
			$business_login        = [ 'email' => 'demo.lawyer@ipfo-demo.test', 'password' => $lawyer_user_id['password'] ];

			$lawyer_post_id = wp_insert_post(
				[
					'post_type'    => BusinessCPT::SLUG,
					'post_title'   => 'Levent Kızılduman & Associates',
					'post_content' => 'Levent Kızılduman & Associates is a Cyprus law firm offering advice across Cyprus and London, led by Managing Partner Levent Kızılduman. The firm\'s practice covers family, corporate, property, real estate, criminal, immigration, litigation, succession and education law, serving clients across more than 30 countries.',
					'post_status'  => 'publish',
					'post_author'  => $lawyer_user_id['id'],
				],
				true
			);
			if ( is_wp_error( $lawyer_post_id ) ) {
				$errors[] = 'Business listing (Levent): ' . $lawyer_post_id->get_error_message();
			} else {
				$refs['posts'][] = $lawyer_post_id;
				if ( $lawyer_term ) {
					wp_set_post_terms( $lawyer_post_id, [ $lawyer_term->term_id ], BusinessCPT::TAXONOMY );
				}
				update_post_meta( $lawyer_post_id, '_ipfo_country_ids', wp_json_encode( [ $country_id ] ) );
				update_post_meta( $lawyer_post_id, '_ipfo_phone', '+90 392 227 56 02' );
				update_post_meta( $lawyer_post_id, '_ipfo_email', 'levent.kizilduman@gmail.com' );
				update_post_meta( $lawyer_post_id, '_ipfo_website', 'https://www.lawyerlevent.com/' );
				update_post_meta( $lawyer_post_id, '_ipfo_address', "Muftu Raci Efendi Avenue, No: 28\nNicosia, Cyprus" );
				update_post_meta( $lawyer_post_id, '_ipfo_credentials', 'Solicitor, Advocate & Legal Consultant. Offices in Nicosia, Limassol and London.' );
				update_post_meta( $lawyer_post_id, '_ipfo_featured', '1' );
				update_user_meta( $lawyer_user_id['id'], '_ipfo_business_post_id', $lawyer_post_id );
			}
		}

		// 8. Listing 2 (Ceres Fertility) — admin-authored, no owner account,
		// to demonstrate the other way a listing can exist in the directory.
		$ceres_post_id = wp_insert_post(
			[
				'post_type'    => BusinessCPT::SLUG,
				'post_title'   => 'Ceres Fertility',
				'post_content' => 'Ceres Fertility is a fertility clinic in Cyprus offering IVF, surrogacy, egg and semen donation, genetic testing, and reciprocal IVF for same-sex female couples. Its tagline is "Unlocking Parenthood, One Miracle at a Time," with a team that includes embryologists, legal advisers and patient coordinators, and multilingual staff (English, German, Turkish and Arabic).',
				'post_status'  => 'publish',
				'post_author'  => get_current_user_id() ?: 1,
			],
			true
		);
		if ( is_wp_error( $ceres_post_id ) ) {
			$errors[] = 'Business listing (Ceres): ' . $ceres_post_id->get_error_message();
		} else {
			$refs['posts'][] = $ceres_post_id;
			if ( $clinic_term && ! is_wp_error( $clinic_term ) ) {
				wp_set_post_terms( $ceres_post_id, [ $clinic_term->term_id ], BusinessCPT::TAXONOMY );
			}
			update_post_meta( $ceres_post_id, '_ipfo_country_ids', wp_json_encode( [ $country_id ] ) );
			update_post_meta( $ceres_post_id, '_ipfo_phone', '+44 7833 779958' );
			update_post_meta( $ceres_post_id, '_ipfo_website', 'https://www.ceresfertility.com/' );
			update_post_meta( $ceres_post_id, '_ipfo_address', 'Yağmur Sk-Erman Apt, No 401, Gönyeli 1010, Cyprus' );
			update_post_meta( $ceres_post_id, '_ipfo_credentials', 'Multilingual team (English, German, Turkish, Arabic). Testimonials from clients in Ireland, the UK and the USA.' );
			update_post_meta( $ceres_post_id, '_ipfo_featured', '0' );
		}

		// 9. Demo IP client, with real access grants to the Denmark guide.
		$client_login = [];
		$client_user  = $this->create_demo_user( 'demo-client', 'demo.client@ipfo-demo.test', Capabilities::CLIENT_ROLE, 'Demo', 'Client' );
		if ( is_wp_error( $client_user ) ) {
			$errors[] = 'Client account: ' . $client_user->get_error_message();
		} else {
			$refs['users'][] = $client_user['id'];
			$client_login    = [ 'email' => 'demo.client@ipfo-demo.test', 'password' => $client_user['password'] ];

			update_user_meta( $client_user['id'], '_ipfo_email_verified', '1' );
			update_user_meta( $client_user['id'], '_ipfo_approval_status', 'approved' );
			update_user_meta( $client_user['id'], '_ipfo_phone', '+45 20 12 34 56' );

			$access = new AccessControlService();
			$access->set_user_country( $client_user['id'], $country_id );
			if ( $guide_id ) {
				$access->grant_guide( $client_user['id'], $guide_id );
			}
		}

		// 10. Demo invitations (left unredeemed so they can be tested live).
		$invitation_links = [];
		$invitation_service = new InvitationService();

		[ $client_inv_id, $client_code, $client_token ] = $invitation_service->create(
			[ 'invite_type' => 'client', 'country_id' => $country_id, 'guide_id' => $guide_id ?: null, 'max_uses' => 1, 'single_use' => true ]
		);
		$refs['invitations'][]        = $client_inv_id;
		$invitation_links['client']   = $invitation_service->build_link( $client_code, $client_token, 'client' );

		[ $biz_inv_id, $biz_code, $biz_token ] = $invitation_service->create(
			[ 'invite_type' => 'business', 'business_category' => $clinic_term && ! is_wp_error( $clinic_term ) ? $clinic_term->term_id : null, 'max_uses' => 1, 'single_use' => true ]
		);
		$refs['invitations'][]      = $biz_inv_id;
		$invitation_links['business'] = $invitation_service->build_link( $biz_code, $biz_token, 'business' );

		update_option( self::OPTION_REFS, $refs );
		update_option( self::OPTION_INSTALLED, 1 );

		return [
			'already_installed' => false,
			'errors'            => $errors,
			'client_login'      => $client_login,
			'business_login'    => $business_login,
			'invitation_links'  => $invitation_links,
			'directory_url'     => ipfo_get_portal_page_url( 'directory' ),
		];
	}

	public function remove(): void {
		$refs = get_option( self::OPTION_REFS, [] );

		foreach ( (array) ( $refs['invitations'] ?? [] ) as $id ) {
			( new InvitationRepository() )->delete( (int) $id );
		}

		foreach ( (array) ( $refs['checklist_items'] ?? [] ) as $id ) {
			( new ChecklistRepository() )->delete_item( (int) $id );
		}

		foreach ( (array) ( $refs['countries'] ?? [] ) as $id ) {
			( new CountryRepository() )->delete( (int) $id );
		}

		foreach ( (array) ( $refs['terms'] ?? [] ) as $id ) {
			wp_delete_term( (int) $id, BusinessCPT::TAXONOMY );
		}

		foreach ( (array) ( $refs['posts'] ?? [] ) as $id ) {
			wp_delete_post( (int) $id, true );
		}

		foreach ( (array) ( $refs['attachments'] ?? [] ) as $id ) {
			wp_delete_attachment( (int) $id, true );
		}

		foreach ( (array) ( $refs['users'] ?? [] ) as $id ) {
			if ( ! function_exists( 'wp_delete_user' ) ) {
				require_once ABSPATH . 'wp-admin/includes/user.php';
			}
			wp_delete_user( (int) $id );
		}

		delete_option( self::OPTION_REFS );
		delete_option( self::OPTION_INSTALLED );
	}

	/** @return array{id:int,password:string}|WP_Error */
	private function create_demo_user( string $login, string $email, string $role, string $first_name, string $last_name ) {
		if ( email_exists( $email ) || username_exists( $login ) ) {
			return new WP_Error( 'ipfo_demo_user_exists', sprintf( 'An account already exists for %s.', $email ) );
		}

		$password = wp_generate_password( 16, false );

		$user_id = wp_insert_user(
			[
				'user_login'   => $login,
				'user_email'   => $email,
				'user_pass'    => $password,
				'first_name'   => $first_name,
				'last_name'    => $last_name,
				'display_name' => trim( $first_name . ' ' . $last_name ),
				'role'         => $role,
			]
		);

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		return [ 'id' => (int) $user_id, 'password' => $password ];
	}

	/** @return int|WP_Error */
	private function sideload_bundled_pdf() {
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			require_once ABSPATH . 'wp-admin/includes/file.php';
			require_once ABSPATH . 'wp-admin/includes/media.php';
		}

		$source = IPFO_PLUGIN_DIR . 'assets/demo/denmark-legal-guide-to-surrogacy-abroad.pdf';
		if ( ! is_readable( $source ) ) {
			return new WP_Error( 'ipfo_demo_missing_pdf', 'The bundled demo PDF could not be found in the plugin package.' );
		}

		$contents = file_get_contents( $source ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		if ( false === $contents ) {
			return new WP_Error( 'ipfo_demo_unreadable_pdf', 'The bundled demo PDF could not be read.' );
		}

		$upload = wp_upload_bits( 'denmark-legal-guide-to-surrogacy-abroad.pdf', null, $contents );
		if ( ! empty( $upload['error'] ) ) {
			return new WP_Error( 'ipfo_demo_upload_failed', (string) $upload['error'] );
		}

		$attachment_id = wp_insert_attachment(
			[
				'post_mime_type' => 'application/pdf',
				'post_title'     => 'Danish Legal Guide to Surrogacy Abroad',
				'post_content'   => '',
				'post_status'    => 'inherit',
			],
			$upload['file']
		);

		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		$metadata = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
		wp_update_attachment_metadata( $attachment_id, $metadata );

		return (int) $attachment_id;
	}
}
