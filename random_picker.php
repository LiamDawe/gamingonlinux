<strong>GamingOnLinux random username picker for key giveaways:</strong><br />
<br />
Names, put each name on a new line: <br />
<textarea id="names" rows="15" cols="100"></textarea><br />
<select id="amount"></select>
<button id="pick" onclick="random_click();">Pick</button><br />
<strong>Results</strong><br />
<div id="result"></div>
<script>
var select = document.getElementById("amount");

for(var i = 1; i < 11; i++)
{
    var el = document.createElement("option");
    el.textContent = i;
    el.value = i;
    select.appendChild(el);
}

function getRandom(arr, how_many)
{
    var result = new Array(how_many),len = arr.length,taken = new Array(len);
    if (how_many > len)
    {
        throw new RangeError("getRandom: more elements taken than available");
    }
    while (how_many--)
    {
        var x = Math.floor(Math.random() * len);
        result[how_many] = arr[x in taken ? taken[x] : x];
        taken[x] = --len;
    }
    return result;
}

function random_click()
{
  var names_array = document.getElementById('names').value.split('\n');

  var random = getRandom(names_array, document.getElementById('amount').value)

  document.getElementById('result').innerHTML = random;
}
</script>
