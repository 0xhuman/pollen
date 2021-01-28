<?php 
    require_once ('USSD-dbConnect.php');

    //function proxyRequest() {
        //$fixieUrl = getenv("FIXIE_URL");
        //$parsedFixieUrl = parse_url($fixieUrl);

        //$proxy = $parsedFixieUrl['host'].":".$parsedFixieUrl['port'];
        //$proxyAuth = $parsedFixieUrl['user'].":".$parsedFixieUrl['pass'];

        //$ch = curl_init($url);
        //curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //curl_setopt($ch, CURLOPT_PROXY, $proxy);
        //curl_setopt($ch, CURLOPT_PROXYUSERPWD, $proxyAuth);
        //curl_close($ch);
      //}
      //proxyRequest();
      //$response = proxyRequest();
      //print_r($response);
?>

<html>
    
  <a href="http://pollen-ussd.000webhostapp.com/chartindex.php"> <button>Dashboard 1  </button> </a>
  <a href="http://pollen-ussd.000webhostapp.com/chartindex2.php"> <button>Dashboard 2  </button> </a>
  <a href="http://pollen-ussd.000webhostapp.com/chartindex3.php"> <button>Dashboard 3  </button> </a>
  <a href="http://pollen-ussd.000webhostapp.com/chartindex4.php"> <button>Dashboard 4  </button> </a>
  <a href="http://pollen-ussd.000webhostapp.com/chartindex5.php"> <button>Dashboard 5  </button> </a>


  <head>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">
    
    // Load bar chart lib
      google.charts.load('current', {'packages':['bar']});
      google.charts.setOnLoadCallback(drawBarChart);
      
    // Load sankey lib
      google.charts.load("current", {packages:["sankey"]});
      google.charts.setOnLoadCallback(drawSankey);

      function drawBarChart() {
        var data = google.visualization.arrayToDataTable([
          ['Date', 'Daily Sessions'],
          <?php
            //$query = "select date, count(*) from session_levels group by date";
            $query = "SELECT date, count(*), IF(level IS NULL, 0, level) AS level, b.Days AS date FROM (SELECT a.Days  FROM (SELECT curdate() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY AS Days FROM       (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS a CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS b CROSS JOIN (SELECT 0 AS a UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) AS c) a WHERE a.Days >= curdate() - INTERVAL 90 DAY) b LEFT JOIN session_levels ON date = b.Days GROUP BY b.Days;";
            $res=mysqli_query($db,$query);
            while($data=mysqli_fetch_array($res)){
                $date=$data['date'];
                $users=$data['count(*)'];
            ?>
            
            ['<?php echo $date;?>',<?php echo $users;?>],
            <?php
               }
               
              ?>
        ]);

        var options = {
          chart: {
            title: 'Daily Sessions',
            subtitle: 'Testing testing 123',
            margin_top: 4000,
          }
        };

        var chart = new google.charts.Bar(document.getElementById('columnchart_material'));

        chart.draw(data, google.charts.Bar.convertOptions(options));
      }
      
      
      function drawSankey() {
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'From');
        data.addColumn('string', 'To');
        data.addColumn('number', 'Weight');
        data.addRows([
              <?php
                
                //$query = "select dw, phonenumber, circleID, amount, from circleTxns GROUP BY phonenumber";
                $query = "select * from circleTxns";
                $res=mysqli_query($db,$query);
                while($data=mysqli_fetch_array($res)){
                        if ($data['dw'] == 'D') {
                            $from=$data['phonenumber'];
                        $to=$data['circleID'];
                        $weight=$data['amount'];
                        }
                        
                        elseif ($data['dw'] == 'W') {
                            $from=$data['circleID'];
                            $to2=$data['phonenumber'];
                            $to= '"(----)"' . $to2 . '"'; 
                            $weight=$data['amount'];
                        }
                            
                        else {
                            //$from=$data['phonenumber'];
                        //$to=$data['circleID'];
                        //$weight=$data['amount'];
                        };
                        
                        //else {
                        //    $from=$data['phonenumber'];
                        //$to=$data['circleID'];
                        
                        //$sql = "select SUM(amount) AS value_sum FROM circleTxns WHERE phonenumber = '{$data['phonenumber']}'";
                        //$amountsum = mysqli_query($db,$sql);
                        //$amount = mysqli_fetch_assoc($amountsum);
                        //$weight=$amount['value_sum'];
                        //}
            ?>
            
            ['<?php echo $from;?>','<?php echo $to;?>',<?php echo $weight;?>],
            <?php
               }
               
              ?>
        ]);
        
            var colors = ['#a6cee3', '#b2df8a', '#fb9a99', '#fdbf6f',
                  '#cab2d6', '#ffff99', '#1f78b4', '#33a02c'];
            
            var options = {
                title: 'Circle Deposits and Withdrawals Sankey Chart',
                height: 600,
                sankey: {
                node: {
                  colors: colors
                },
                link: {
                  colorMode: 'gradient',
                  colors: colors
                }
                }
        };
    
            var chart = new google.visualization.Sankey(document.getElementById('sankey_multiple'));
            chart.draw(data, options);
          }
      

    </script>
  </head>
  <body>
    <div id="columnchart_material" style="width: 800px; height: 500px; margin-top: 20px; margin-bottom: 30px;"></div>
    <div id="sankey_multiple" style="width: 800px; height: 500px;"></div>
  </body>
  
</html>
