<?php 
get_header();

if ( have_posts() ) :
	while ( have_posts() ) : the_post();
		$name = get_post_meta(get_the_ID(), 'name')[0];
		$login = get_the_title();
		$avatar = get_post_meta(get_the_ID(), 'avatar')[0];
		$company = get_post_meta(get_the_ID(), 'company')[0];
		$location = get_post_meta(get_the_ID(), 'location')[0];
		$blog = get_post_meta(get_the_ID(), 'blog')[0];
		$github = get_post_meta(get_the_ID(), 'github')[0];
		$repositories = get_post_meta(get_the_ID(), 'public_repos')[0];
		$gists = get_post_meta(get_the_ID(), 'public_gists')[0];
		$last_activity_fetch = get_post_meta(get_the_ID(), 'last_activity_fetch')[0];
		$minutes_ago = round((time() - $last_activity_fetch) / 60);

		?>

		<div class="container">
			<div class="row">
				<div class="col-md-4 order-md-2">
					<img src="<?php echo $avatar; ?>" alt="<?php echo $login; ?> avatar" class="img-fluid avatar" />
				</div>

				<div class="col-md-8 d-flex flex-column justify-content-center order-md-1">
					<?php
						if ($name !== '') {
							$names = explode(' ', $name);
							?>

							<h1><?php echo $names[0]; 

							if (count($names) > 1) {
								unset($names[0]);
								$names = implode(' ', $names);

								echo '<br /><span class="second">' . $names . '</span>';
							}
							?>

							</h1>

							<h2 class="username">@<?php the_title(); ?></h2>
							<?php
						} else { ?>
							<h1><?php the_title(); ?></h1>
							<?php
						}
					?>
					<div class="bio"><?php the_excerpt(); ?></div>
				</div>
			</div>

			<div class="row">
				<div class="col-md-4">
					<?php if (trim($company) !== '') { ?><div class="fact wrench"><?php echo $company; ?></div><?php } ?>
					<?php if (trim($location) !== '') { ?><div class="fact map-marker"><?php echo $location; ?></div><?php } ?>
					<?php if (trim($blog) !== '') { ?><div class="fact home"><?php echo $blog; ?></div><?php } ?>
				</div>

				<div class="col-md-4">
					<div class="fact github"><?php echo $github; ?></div>
					<?php if ($repositories > 0) { ?><div class="fact github">public repositories: <?php echo $repositories; ?></div><?php } ?>
					<?php if ($gists > 0) { ?><div class="fact github">public gists: <?php echo $gists; ?></div><?php } ?>
				</div>
			</div>
		</div>

		<div class="divider">
			<div class="container">
				<h2>Latest GitHub activity <span>(fetched <?php echo $minutes_ago; ?> minutes ago)</span></h2>
			</div>	
		</div>

		<div class="container">
			<?php the_content(); ?>
		</div>

		<div class="divider">
			<div class="container">
				<h2>Latest Blog posts</h2>
			</div>	
		</div>

		<div class="container">
			<ul>
				<?php
				$fposts = get_post_meta(get_the_ID(), 'feedposts')[0];
				foreach($fposts as $fpost) {
					echo '<li>' . $fpost['link'] . '</li>';
				}
				?>
			</ul>
		</div>

		<?php

	endwhile;
endif;

get_footer();
?>



body {
	font-family: "open_sanslight";
}

.avatar {
	width: 50vw;
    border-radius: 50%;
    margin: 0 auto;
    display: block;
}

h1 {
	text-align: center;
}

h2 {
	text-align: center;
}

.bio {
	text-align: center;
	margin: 2rem 0;
}

.divider {
	margin: 3rem 0;
	height: calc(3rem - 12px);
	padding: 0;
	background: #182a3b;
    color: #fff;
}

.divider h2 {
	margin: 0;
	transform: translate(0, 8px);
	font-weight: bold;
	line-height: 1;
}

.divider h2 span {
	font-size: 50%;
	font-weight: 100;
	color: #afafaf;
	display: block;
}

@media all and (min-width: 768px) {
	.avatar {
		width: auto;
		border-radius: 0;
	}

	h1 {
		text-align: left;
	}

	h2 {
		text-align: left;
	}

	.bio {
		text-align: left;
		margin: .5rem 0 0 0;
	}

	.divider {
		padding: 10px 0 0 0;
		margin: 3rem 0 2rem 0;
		height: calc(3rem - 2px);
	}

	.divider h2 span {
		font-size: 50%;
		font-weight: 100;
		display: inline;
		color: #fff;
	}

}

h1 {
	font-family: "open_sansbold";
	line-height: 3rem;
	font-size: 3.5rem;
}

h2.username {
	color: #afafaf;
}

.second {
	color: #468CC8;
}

.fact {
	margin: 0 0 0 15px;
}

.fact::before {
	font-family: "FontAwesome";
	margin-left: -15px;
	margin-right: 10px;
	display: inline-block;
    width: 15px;
    text-align: center;
}

.fact.wrench:before {
	content: "\f0ad";
}

.fact.map-marker:before {
  content: "\f041";
}

.fact.home:before {
  content: "\f015";
}

.fact.github:before {
  content: "\f09b";
}


ul {
	padding: 15px;
}

ul li {
	list-style: none;
	margin-bottom: 3px;
}

li:before {
	content: '';
	display: inline-block;
	height: 15px;
	margin: 0 10px 0 -15px;
	width: 15px;
	text-align: center;
}

li.repo-push:before {
	content: url(../img/octicons/repo-push.svg);
}

li.star:before {
	content: url(../img/octicons/star.svg);
}

li.comment-discussion:before {
	content: url(../img/octicons/comment-discussion.svg);
}

li.issue-opened:before {
	content: url(../img/octicons/issue-opened.svg);
}

li.issue-closed:before {
	content: url(../img/octicons/issue-closed.svg);
}

li.issue-reopened:before {
	content: url(../img/octicons/issue-reopened.svg);
}

li.pull-request:before {
	content: url(../img/octicons/git-pull-request.svg);
}

li.repo-forked:before {
	content: url(../img/octicons/repo-forked.svg);
}

li.watch:before {
	content: url(../img/octicons/watch.svg);
}

li.create-branch:before {
	content: url(../img/octicons/git-branch.svg);
}