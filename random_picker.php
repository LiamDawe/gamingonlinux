<?php
$options = '';
for ($i = 1; $i <= 10; $i++)
{
  $options .= '<option value="'.$i.'">'.$i.'</a>';
}
?>
<strong>GamingOnLinux random username picker for key giveaways:</strong><br />
<br />
<form method="post" action="random_picker.php">
  Names, put each name on a new line: <br />
  <textarea name="names" rows="15" cols="100"><?php echo $_POST['names']; ?></textarea><br />
  <select name="amount"><?php echo $options; ?></select>
  <button type="submit">Pick</button>
</form>
<?php if (isset($_POST['names']))
{
  $lines = explode("\n", $_POST['names']);
  //print_r($lines);
  $rand_keys = array_rand($lines, $_POST['amount']);
  //print_r($rand_keys);
  if ($_POST['amount'] == 1)
  {
    echo $lines[$rand_keys];
  }
  else
  {
    foreach ($rand_keys as $key)
    {
      echo $lines[$key] . '<br />';
    }
  }
}
?>
