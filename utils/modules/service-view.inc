<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>LODSPeaKr Basic Menu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <link href="css/bootstrap.min.css" rel="stylesheet" type="text/css" media="screen" />
    <style>
      body {
        padding-top: 60px; /* 60px to make the container go all the way to the bottom of the topbar */
      }
      .wait{
        background-image:url('img/wait.gif');
        background-repeat:no-repeat;
        padding-right:20px;
        background-position: right;
      }
    </style>
    <link href="css/bootstrap-responsive.min.css" rel="stylesheet" type="text/css" media="screen" />
    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    <script type="text/javascript" src="js/bootstrap-typeahead.js"></script>
    <script type="text/javascript">
    $(document).ready(function(){
        $('.typeahead').typeahead({
            source: function (typeahead, query) {
              $('.typeahead').addClass('wait');[]
              return $.get('search/'+encodeURIComponent(query), { }, function (data) {
                  $('.typeahead').removeClass('wait');[]
                  return typeahead.process(data);
              }, 'json');
            },
            onselect: function (obj) {
              $('.typeahead').attr('disabled', true);
              window.location = obj.uri;
            }
        });
    });
    </script>
  </head>
  <body>
 <div class="navbar navbar-fixed-top">
      <div class="navbar-inner">
        <div class="container">
          <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </a>
          <a class="brand" href="#">LODSPeaKr</a>
          <div class="nav-collapse">
            <ul class="nav">
              <li class="active"><a href="#">Home</a></li>
              <li><a href="classes">Classes</a></li>
              <li><a href="namedGraphs">Named Graphs</a></li>
            </ul>
            <form class="navbar-search pull-left" action="">
              <input type="text" data-provide="typeahead" class="typeahead search-query span2" placeholder="Search"/>
            </form>
          </div><!--/.nav-collapse -->
        </div>
      </div>
    </div>

    <div class="container">
      <h1>My new service</h1>
 	    <ul>
    {% for row in models.main %}
        <li><a href="{{lodspk.home}}instances/{{ row.resource.curie }}">{{row.resource.curie}}</a></li>
    {% endfor %}
      </ul>
    </div>
  </body>
</html>
