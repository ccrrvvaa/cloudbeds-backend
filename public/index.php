<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

$baseUrl = $_SERVER['HTTP_HOST'];
$ajaxUrl = $baseUrl . '/ajax.php';

?>

<html>
    <head>
        <title>Cloudbeds Backend Test</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.8.2/css/all.css" integrity="sha384-oS3vJWv+0UjzBfQzYUhtDYW+Pj2yciDJxpsK1OYPAYjqT085Qq/1cq5FLXAZQ7Ay" crossorigin="anonymous">
        <link rel="stylesheet" href="//<?php echo $baseUrl ?>/css/bulma.min.css">
        <style>
            article {
                min-height: 55%;
            }

            form {
                padding: 1.5rem;
            }

            .modal-content {
                background: white;
            }
        </style>
    </head>
    <body>
        <header>
            <br><h1 class="title has-text-centered">Cloudbeds Backend Test</h1>
        </header>
        <article>
            <nav class="level"></nav>
            <section>
                <div class="container">
                    <div class="level-right">
                        <p class="level-item"><a id="new" class="button is-link">New</a></p>
                    </div>

                    <table id="list" class="table is-fullwidth">
                        <thead>
                            <tr>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Price</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>

                    <div class="level-right">
                        <p class="level-item"><a id="delete-all" class="button is-warning">Delete All</a></p>
                    </div>

                    <div id="addModal" class="modal">
                        <div class="modal-background"></div>
                        <div class="modal-content">
                            <form class="form">
                                <div class="field">
                                    <label class="label">Start Date</label>
                                    <div class="control">
                                        <input class="input" type="date" id="start_date">
                                    </div>
                                </div>
                                <div class="field">
                                    <label class="label">End Date</label>
                                    <div class="control">
                                        <input class="input" type="date" id="end_date">
                                    </div>
                                </div>
                                <div class="field">
                                    <label class="label">Price</label>
                                    <div class="control">
                                        <input class="input" type="number" step="0.01" id="price">
                                    </div>
                                </div>
                                <div class="field level-right">
                                    <a id="save" class="button is-info">Save</a>
                                </div>
                            </form>
                        </div>
                        <button class="modal-close is-large" aria-label="close"></button>
                    </div>
                </div>
            </section>
            <br>
        </article>
        <footer class="footer">
            <div class="content has-text-centered">&copy; Copyright 2019 ccrrvvaa</div>
        </footer>
        
        <script>
            var xmlhttp = new XMLHttpRequest()
            var urlList = "//<?php echo $ajaxUrl ?>?action=list"
            var newButton = document.getElementById('new')
            var saveButton = document.getElementById('save')
            var deleteAllButton = document.getElementById('delete-all')

            function loadList(data) {
                xmlhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        var data = JSON.parse(this.responseText)
                        data = data.data

                        var tbody = document.querySelector('#list tbody')
                        tbody.innerHTML = ''

                        if (data && data.length > 0) {
                            data.forEach(ent => {
                                var startDate = ent.startDate.date.substring(0, 10)
                                var endDate = ent.endDate.date.substring(0, 10)

                                tbody.innerHTML += `
                                    <tr ent_id = ${ent.id}>
                                        <td>${startDate}</td>
                                        <td>${endDate}</td>
                                        <td>${ent.price}</td>
                                        <td><a onclick='removeItem(this)' style="color:red"><span class="icon"><i class="fas fa-trash-alt"></i></span></a></td>
                                    </tr>
                                `;
                            });
                        }
                    }
                }

                xmlhttp.open("GET", urlList, true)
                xmlhttp.send()
            }

            function openForm() {
                var modal = document.getElementById('addModal')
                modal.classList.add('is-active')
                modal.querySelector('form').reset()
            }

            function closeForm() {
                var modal = document.getElementById('addModal')
                modal.classList.remove('is-active')
                modal.querySelector('form').reset()
            }

            function removeItem(link) {
                var tr = link.parentNode.parentNode

                if (confirm('Are you sure to delete this item?')) {
                    var formData = new FormData();
                    formData.append("id", tr.getAttribute('ent_id'))

                    xmlhttp.onreadystatechange = function() {
                        if (this.readyState == 4 && this.status == 200) {
                            loadList()
                        }
                    }

                    xmlhttp.open("POST", "//<?php echo $ajaxUrl ?>?action=delete");
                    xmlhttp.send(formData);
                }
            }

            newButton.addEventListener('click', function() {
                openForm()
            })

            saveButton.addEventListener('click', function() {
                var startDate = document.getElementById('start_date')
                var endDate = document.getElementById('end_date')
                var price = document.getElementById('price')

                var formData = new FormData();
                formData.append("startDate", startDate.value)
                formData.append("endDate", endDate.value)
                formData.append("price", price.value)

                xmlhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        closeForm()
                        loadList()
                    }
                }

                xmlhttp.open("POST", "//<?php echo $ajaxUrl ?>?action=insert");
                xmlhttp.send(formData);
            })

            deleteAllButton.addEventListener('click', function () {
                if (confirm('Are you sure you want to delete all items?')) {
                    xmlhttp.onreadystatechange = function() {
                        if (this.readyState == 4 && this.status == 200) {
                            loadList()
                        }
                    }

                    xmlhttp.open("GET", "//<?php echo $ajaxUrl ?>?action=clear");
                    xmlhttp.send();
                }
            })

            loadList()
        </script>
    </body>
</html>