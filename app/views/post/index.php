<!-- Header-->
<header id="blog" class="bg-dark"></header>

<!-- Posts section-->
<section id="posts" class="py-5">
    <div class="container px-5 my-2">
       <div class="row gx-5 justify-content-center">
           <div class="col-12">
			   <?php
			   foreach ($posts as $post):
				   require VIEWS . '/post/_post.php';
			   endforeach;
			   ?>
           </div>
       </div>
    </div>
</section>
