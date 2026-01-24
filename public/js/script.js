let funcionActual = null;

$(function () {
  $.fn.serializeObject = function () {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function () {
      if (o[this.name]) {
        if (!o[this.name].push) {
          o[this.name] = [o[this.name]];
        }
        o[this.name].push(this.value || "");
      } else {
        o[this.name] = this.value || "";
      }
    });
    return o;
  };

  $("#btn-agregar").click(function () {
    funcionActual = "add";
    $("#btn-agregar-aceptar").text("Agregar");
    $("#form-add input").val("");
    $("#form-add select").val("");
    $("#form-add textarea").html("");
    $("#form-add .input-dialog-key").each(function (i, val) {
      $(val).show();
      $(val).removeAttr("disabled");
      $("#form-add label[for='" + $(val).attr("name") + "']").show();
      $("#form-add .input-dialog-key-hidden").remove();
    });
    $("#dialog-add").dialog("open");
  });
  $("#btn-agregar-cancel").click(function () {
    $("#dialog-add").dialog("close");
    funcionActual = null;
  });
  $("#dialog-add").dialog({
    resizable: false,
    height: "auto",
    width: 600,
    modal: true,
    autoOpen: false,
  });

  $("#dialog-delete").dialog({
    resizable: false,
    height: "auto",
    modal: true,
    autoOpen: false,
  });
  $("#dialog-activate").dialog({
    resizable: false,
    height: "auto",
    modal: true,
    autoOpen: false,
  });

  $(".dialog-asignacion").dialog({
    resizable: false,
    height: "auto",
    width: 600,
    modal: true,
    autoOpen: false,
  });

  $(".datepicker-input").datepicker({
    dateFormat: "yy-mm-dd",
    changeYear: true,
    yearRange: "-100:+10",
  });

  $(".dtable").DataTable({
    language: {
      lengthMenu: "Mostrar _MENU_ registros por página",
      zeroRecords: "No se han encontrado registros.",
      info: "Página _PAGE_ de _PAGES_",
      infoEmpty: "No existen registros.",
      infoFiltered: "(Filtrado de _MAX_ registros totales)",
      search: "Buscar:",
      paginate: {
        previous: "Anterior",
        next: "Siguiente",
      },
    },
    lengthMenu: [10, 20, 30, 40, 50, 100, 200, 300],
    pageLength: 10,
  });

 $('#tableReporte1').DataTable({
    dom: 'Bfrtip',
    buttons: [
      //'csv', 'excel', 'pdf', 'print'
      {
        extend: 'excel',
        title: 'Reporte 1'
      },
      {
          extend: 'pdf',
          title: 'Candidatos a promoción docente'
      },
      {
          extend: 'print',
          title: 'Reporte 1'
      },
      {
          extend: 'csv',
          title: 'Reporte 1'
      }
    ],
    
    language: {
      lengthMenu: "Mostrar _MENU_ registros por página",
      zeroRecords: "No se han encontrado registros.",
      info: "Página _PAGE_ de _PAGES_",
      infoEmpty: "No existen registros.",
      infoFiltered: "(Filtrado de _MAX_ registros totales)",
      search: "Buscar:",
      paginate: {
        previous: "Anterior",
        next: "Siguiente",
      },
      buttons: {
        print: 'Imprimir',
        copy: 'Copiar',
        copyTitle: 'Información copiada',
        copyKeys: 'Use your keyboard or menu to select the copy command'
      }
    },
    lengthMenu: [10, 20, 30, 40, 50, 100, 200, 300],
    pageLength: 10,
    colReorder: true
  });

 $('#tableReporte2').DataTable({
    dom: 'Bfrtip',
    buttons: [
      //'csv', 'excel', 'pdf', 'print'
      {
        extend: 'excel',
        title: 'Reporte 2'
      },
      {
          extend: 'pdf',
          title: 'Reporte 2'
      },
      {
          extend: 'print',
          title: 'Reporte 2'
      },
      {
          extend: 'csv',
          title: 'Reporte 2'
      }
    ],
    
    language: {
      lengthMenu: "Mostrar _MENU_ registros por página",
      zeroRecords: "No se han encontrado registros.",
      info: "Página _PAGE_ de _PAGES_",
      infoEmpty: "No existen registros.",
      infoFiltered: "(Filtrado de _MAX_ registros totales)",
      search: "Buscar:",
      paginate: {
        previous: "Anterior",
        next: "Siguiente",
      },
      buttons: {
        print: 'Imprimir',
        copy: 'Copiar',
        copyTitle: 'Información copiada',
        copyKeys: 'Use your keyboard or menu to select the copy command'
      }
    },
    lengthMenu: [10, 20, 30, 40, 50, 100, 200, 300],
    pageLength: 10,
  });

 $('#tableLogs').DataTable({
    dom: 'Bfrtip',
    buttons: [
      //'csv', 'excel', 'pdf', 'print'
      {
        extend: 'excel',
        title: 'Logs del Sistema'
      },
      {
          extend: 'pdf',
          title: 'Logs del Sistema'
      },
      {
          extend: 'print',
          title: 'Logs del Sistema'
      },
      {
          extend: 'csv',
          title: 'Logs del Sistema'
      }
    ],
    
    language: {
      lengthMenu: "Mostrar _MENU_ registros por página",
      zeroRecords: "No se han encontrado registros.",
      info: "Página _PAGE_ de _PAGES_",
      infoEmpty: "No existen registros.",
      infoFiltered: "(Filtrado de _MAX_ registros totales)",
      search: "Buscar:",
      paginate: {
        previous: "Anterior",
        next: "Siguiente",
      },
      buttons: {
        print: 'Imprimir',
        copy: 'Copiar',
        copyTitle: 'Información copiada',
        copyKeys: 'Use your keyboard or menu to select the copy command'
      }
    },
    lengthMenu: [10, 20, 30, 40, 50, 100, 200, 300],
    pageLength: 10,
  });

});






function cargarDialogActualizar(data) {
  funcionActual = "update";
  $("#btn-agregar-aceptar").text("Actualizar");
  for (var k in data) {
    console.log(data[k]);
    if (typeof data[k] !== "function") {
      $("#form-add input[name='" + k + "']").val(data[k]);
      $("#form-add select[name='" + k + "']").val(data[k]);
      $("#form-add textarea[name='" + k + "']").html(data[k]);
    }
  }
  $(".input-dialog-key").each(function (i, val) {
    $(val).hide();
    $(val).attr("disabled", "disabled");
    $("#form-add label[for='" + $(val).attr("name") + "']").hide();
    $("#dialog-add form").append(
      "<input name='" +
        $(val).attr("name") +
        "' value='" +
        data[$(val).attr("name")] +
        "' type='hidden' class='input-dialog-key-hidden' />"
    );
  });
  $("#dialog-add").dialog("open");
}

function cargarDialogAsignacionOpciones(data, id, id_form) {
  $(id_form + " input.id-asociacion").val(id);
  $(id_form + " input[name^='check").prop("checked", false);
  for (var k in data) {
    //console.log(data[k]);
    // $(id_form + " input[name='check[" + data[k] + "]'").prop('checked', true);
    $(`#${data[k]}`).prop("checked", true);
  }
  $(id_form).dialog("open");
}

function cargarInfo(data) {
  console.log(data);
  for (var k in data) {
    console.log(data[k]);
    if (typeof data[k] !== "function") {
      $("input[name='" + k + "']").val(data[k]);
      $("select[name='" + k + "']").val(data[k]);
      $("textarea[name='" + k + "']").html(data[k]);
    }
  }
}

function agregarDialogo(tipo, mensaje) {
  if (tipo === 1) {
    color = "text-success";
    icono = "ui-icon-circle-check";
  } else if (tipo === 2) {
    color = "text-danger";
    icono = "ui-icon-circle-close";
  } else {
    color = "text-info";
    icono = "ui-icon-circle-check";
  }
  $("body").append(
    '<div id="dialogt" title="Información"><p class="' +
      color +
      '"><span class="ui-icon ' +
      icono +
      '" style="float:left; margin:0 7px 50px 0;"></span>' +
      mensaje +
      "</p></div>"
  );
  $("#dialogt").dialog({
    modal: true,
    buttons: {
      Aceptar: function () {
        $(this).dialog("close");
        $("#dialogt").remove();
      },
    },
  });
}

function agregarDialogoCarga() {
  $("body").append(
    '<div id="loading-section" style="display: none;"><div class="ui-widget-overlay ui-front" style="z-index: 100;"></div><div class="loader"></div></div>'
  );
  $("#loading-section").fadeIn();
}

function removerDialogoCarga() {
  $("#loading-section").fadeOut();
  $("#loading-section").remove();
}

function limpiarFormulario(formid) {
  $("#" + formid + " input").val("");
  $("#" + formid + " select").val("");
  $("#" + formid + " textarea").html("");
  $("#" + formid + " .input-dialog-key").each(function (i, val) {
    $(val).show();
    $(val).removeAttr("disabled");
    $("#" + formid + " label[for='" + $(val).attr("name") + "']").show();
    $("#" + formid + " .input-dialog-key-hidden").remove();
  });
}

function llenarFormulario(formid, data) {
  for (var k in data) {
    if (typeof data[k] !== "function") {
      $("#" + formid + " input[name='" + k + "']").val(data[k]);
      $("#" + formid + " select[name='" + k + "']").val(data[k]);
      $("#" + formid + " textarea[name='" + k + "']").html(data[k]);
    }
  }
  $(".input-dialog-key").each(function (i, val) {
    $(val).hide();
    $(val).attr("disabled", "disabled");
    $("#" + formid + " label[for='" + $(val).attr("name") + "']").hide();
    $("#" + formid + " form").append(
      "<input name='" +
        $(val).attr("name") +
        "' value='" +
        data[$(val).attr("name")] +
        "' type='hidden' class='input-dialog-key-hidden' />"
    );
  });
}

function hacerPeticionApi(
  servicio,
  data,
  callback,
  showSuccessDialog,
  htmlElementRef,
  vars
) {
  agregarDialogoCarga();
  var jqxhr = $.ajax(servicio, { data: data, method: "POST", async: true })
    .done(function (data) {
      //leer respuesta...
      if (data.error === true) {
        agregarDialogo(2, data.descripcion);
      } else {
        if (showSuccessDialog == true) {
          agregarDialogo(1, data.descripcion);
        }
      }
      if (htmlElementRef === false) {
        callback(data, vars);
      } else {
        callback(data, htmlElementRef, vars);
      }
    })
    .fail(function () {
      //mensaje de error...
      agregarDialogo(
        2,
        "Ha ocurrido un error al intentar realizar su solicitud, por favor intentar luego o contactar a soporte técnico."
      );
    })
    .always(function () {
      removerDialogoCarga();
    });
}

function dialogEliminar(data) {
  $("#form-dialog-delete").remove("input");
  $("#form-dialog-delete").append(
    "<input type='hidden' name= 'action' value='eliminar' />"
  );
  for (var key in data) {
    if (data.hasOwnProperty(key)) {
      $("#form-dialog-delete").append(
        "<input type='hidden' name= '" + key + "' value='" + data[key] + "' />"
      );
    }
  }
  $("#dialog-delete").dialog("open");
}

function dialogActivateUser(data) {
  $("#form-dialog-activate").remove("input");
  $("#form-dialog-activate").append(
    "<input type='hidden' name= 'action' value='activaruser' />"
  );
  for (var key in data) {
    if (data.hasOwnProperty(key)) {
      $("#form-dialog-activate").append(
        "<input type='hidden' name= '" + key + "' value='" + data[key] + "' />"
      );
    }
  }
  $("#dialog-activate").dialog("open");
}

//Funcion general para validar el tamaño del archivo

function validateFile(input) {
  const fileSize = input.files[0].size / 1024 / 1024; // in MiB
  if (fileSize > 2) {
    alert("File size exceeds 2 MiB");
    // $(file).val(''); //for clearing with Jquery
  } else {
    // Proceed further
    console.log("Todo ok");
  }
}

function validarExtension(input) {
  var fileName = input.value;
  var extensionesPermitidas = /(\.pdf)$/i;
  const maxSize = 6 * 1024 * 1024; // 6MB - límite máximo
  const file = input.files[0];

  // Validar que hay un archivo seleccionado
  if (!file) {
    return false;
  }

  // Validar extensión
  if (!extensionesPermitidas.exec(fileName)) {
    Swal.fire({
      title: 'Error de formato',
      text: 'Solo se permiten archivos PDF. Por favor, convierte tu archivo.',
      icon: 'error',
      confirmButtonText: 'Aceptar',
      confirmButtonColor: '#05142b',
    });
    input.value = '';
    return false;
  }

  const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);

  // Si el archivo excede 6MB - Sugerir compresión online
  if (file.size > maxSize) {
    Swal.fire({
      title: '⚠️ Archivo demasiado grande',
      html: `<p>El archivo pesa <strong>${fileSizeMB}MB</strong>.</p>
             <p>El límite máximo permitido es <strong>6MB</strong>.</p>
             <hr>
             <p><strong>📋 Por favor, comprime tu PDF usando UNA de estas herramientas gratuitas:</strong></p>
             <div style="text-align: left; background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0;">
               <p><strong style="color: #05142b;">🥇 Opción 1: PDF Candy (MÁS EFECTIVO)</strong></p>
               <ol style="margin-left: 20px;">
                 <li>Visita <a href="https://www.pdfcandy.com/es/compress-pdf.html" target="_blank" style="color: #05142b;"><u>PDF Candy</u></a></li>
                 <li>Carga tu archivo PDF</li>
                 <li>Espera a que se comprima</li>
                 <li>Descarga el archivo comprimido</li>
               </ol>

               <p style="margin-top: 15px;"><strong style="color: #05142b;">🥈 Opción 2: IlovePDF</strong></p>
               <ol style="margin-left: 20px;">
                 <li>Visita <a href="https://www.ilovepdf.com/es/comprimir_pdf" target="_blank" style="color: #05142b;"><u>IlovePDF</u></a></li>
                 <li>Selecciona tu PDF</li>
                 <li>Comprime y descarga</li>
               </ol>

               <p style="margin-top: 15px;"><strong style="color: #05142b;">🥉 Opción 3: Online Converter</strong></p>
               <ol style="margin-left: 20px;">
                 <li>Visita <a href="https://www.onlineconverter.com/compress-pdf" target="_blank" style="color: #05142b;"><u>Online Converter</u></a></li>
                 <li>Sube tu archivo</li>
                 <li>Comprime y descarga</li>
               </ol>
             </div>
             <hr>
             <p style="font-size: 12px; color: #d9534f; background: #f8d7da; padding: 10px; border-radius: 4px; border-left: 4px solid #d9534f;">
               <strong>⚠️ Nota importante:</strong> Es posible que estas herramientas no logren comprimir demasiado tu archivo, especialmente si contiene muchas imágenes de alta resolución. En ese caso, intenta:
               <ul style="margin-top: 8px; margin-bottom: 0;">
                 <li>Reducir la resolución de las imágenes</li>
                 <li>Eliminar páginas innecesarias</li>
                 <li>Probar con diferentes herramientas</li>
                 <li>Recomendación: Elegir compresión máxima en las herramientas</li>
               </ul>
             </p>
             <hr>
             <p style="font-size: 12px; color: #666;"><strong>💡 Consejo:</strong> Intenta primero con PDF Candy, generalmente comprime más que las otras.</p>`,
      icon: 'warning',
      confirmButtonText: 'Entendido, voy a comprimir',
      confirmButtonColor: '#05142b',
      width: '650px'
    });
    input.value = '';
    return false;
  }

  // Archivo válido (menor o igual a 6MB)
  return true;
}


// Example starter JavaScript for disabling form submissions if there are invalid fields
(function () {
  //console.log("Validacion del formulario");
  // Fetch all the forms we want to apply custom Bootstrap validation styles to
  let forms = document.querySelectorAll(".needs-validation");

  // Loop over them and prevent submission
  Array.prototype.slice.call(forms).forEach(function (form) {
    form.addEventListener(
      "submit",
      function (event) {
        if (!form.checkValidity()) {
          event.preventDefault();
          event.stopPropagation();
        }

        form.classList.add("was-validated");
      },
      false
    );
  });
})();

//Funciones para administrar los formularios de los meritos academicos

function tipoCargo(selectObject) {
  // const value = selectObject.value;
  console.log("tipo de cargo desempeñado");
  // console.log(value);
}

const selecciones = [
  {
    type: "4.1",
    value: "4.1.1",
    name: "Libro o tesis",
    puntos: 6,
  },
  {
    type: "4.1",
    value: "4.1.2",
    name: "Por cada ensayo, articulo de revista o folleto",
    puntos: 3,
  },
  {
    type: "4.2",
    value: "4.2.1",
    name: "Hojas de trabajo o laboratorios (10 o más)",
    puntos: 2,
  },
  {
    type: "4.2",
    value: "4.2.1",
    name: "Por cada documento (6 o más hojas)",
    puntos: 2,
  },
  {
    type: "4.2",
    value: "4.2.3",
    name: "Por cada presentación (digital) de Conferencias, Charlas y/o cursos de implementación y/o actualización académica",
    puntos: 2,
  },
];

const clearSelect = (select) => {
  for (let i = select.options.length; i >= 0; i--) {
    if (i != 0) {
      select.remove(i);
    }
  }
};

function validateDocument(selectObject) {
  const value = selectObject.value;
  let categorias = selecciones.filter((e) => e.type == value);
  const select = document.getElementById("subcategoria");
  clearSelect(select);
  for (const option of categorias) {
    let newOption = document.createElement("option");
    newOption.value = option.value;
    newOption.text = option.name;
    select.add(newOption);
  }
  select.selectedIndex = 0;
  document.querySelector("#label_pts").innerHTML = "0";
  document.querySelector("#puntos").value = 0;
}

function validateSubCategoriaDocument(selectObject) {
  const value = selectObject.value;
  let valueObject = selecciones.find((e) => e.value == value);
  document.querySelector("#label_pts").innerHTML = valueObject.puntos;
  document.querySelector("#puntos").value = valueObject.puntos;
}

function validateFormacion(selectObject) {
  const value = selectObject.value;
  if (value == "nivel_intermedio") {
    console.log("Es nivel intermedio");
    document.querySelector("#graduado").checked = true;
    document.querySelector("#pensum").disabled = true;
    document.querySelector("#label_year").innerHTML = "Año de graduación *";
    document.querySelector("#label_pts").innerHTML = "5";
  } else {
    document.querySelector("#graduado").checked = false;
    document.querySelector("#pensum").checked = false;
    document.querySelector("#pensum").disabled = false;
    document.querySelector("#label_pts").innerHTML = "0";
  }
}

function validateCategoria(radioObject) {
  const value = radioObject.value;
  let nivelOptions = document.getElementById("nivel_titulo");
  let nivel = nivelOptions.options[nivelOptions.selectedIndex].value;
  //console.log(value);
  if (value == "pensum") {
    document.querySelector("#label_year").innerHTML = "Año de cierre de pensum *";
    if (nivel == "doctorado") {
      document.querySelector("#label_pts").innerHTML = "9";
      document.querySelector("#puntos").value = 9;
    } else if (nivel == "maestria") {
      document.querySelector("#label_pts").innerHTML = "8";
      document.querySelector("#puntos").value = 8;
    } else if (nivel == "licenciatura") {
      document.querySelector("#label_pts").innerHTML = "6";
      document.querySelector("#puntos").value = 6;
    }
  }
  if (value == "graduado") {
    document.querySelector("#label_year").innerHTML = "Año de graduación *";
    if (nivel == "doctorado") {
      document.querySelector("#label_pts").innerHTML = "10";
      document.querySelector("#puntos").value = 10;
    } else if (nivel == "maestria") {
      document.querySelector("#label_pts").innerHTML = "8.5";
      document.querySelector("#puntos").value = 8.5;
    } else if (nivel == "licenciatura") {
      document.querySelector("#label_pts").innerHTML = "7";
      document.querySelector("#puntos").value = 7;
    } else if (nivel == "nivel_intermedio") {
      document.querySelector("#label_pts").innerHTML = "5";
      document.querySelector("#puntos").value = 5;
    }
  }
}

const seleccionesCargos = [
  {
    type: "3.1",
    value: "3.1.1",
    name: "1 cargo o más",
    puntos: 4,
  },
  {
    type: "3.2",
    value: "3.2.1",
    name: "Dirección y Programa Interciclos",
    puntos: 4,
  },
  {
    type: "3.2",
    value: "3.2.2",
    name: "Coordinador de nivel, área o departamento",
    puntos: 3,
  },
  {
    type: "3.2",
    value: "3.2.3",
    name: "Coordinador de curso",
    puntos: 1,
  },
  {
    type: "3.2",
    value: "3.2.4",
    name: "Otros nombramientos",
    puntos: 1,
  },
  {
    type: "3.3",
    value: "3.3.1",
    name: "2 cargos o más",
    puntos: 4,
  },
  {
    type: "3.3",
    value: "3.3.2",
    name: "1 cargo",
    puntos: 2,
  },
  {
    type: "3.4",
    value: "3.4.1",
    name: "Por cada cargo",
    puntos: 1,
  },
];

function validateTipoCargo(selectObject) {
  const value = selectObject.value;

  if(value == '3.2'){
    document.querySelector("#nota").innerHTML =   `NO CUENTAN LOS NOMBRAMIENTOS DE ASESORÍA, CONSULTORÍA DE TESIS
    NI PARTICIPACION EN EXAMENES (Privados o públicos), por ser inherentes al cargo de profesores.`;
  }else if(value == '3.3'){
    document.querySelector("#nota").innerHTML =   `NOTA: Los cargos por ELECCIÓN fuera de la USAC, se refiere a todos 
    aquellos, donde el docente es electo por INSTITUCIÓN Y ORGANISMO, únicamente afines al proceso académico 
    y profesional del profesor.`;
  }else if(value == '3.4'){
    document.querySelector("#nota").innerHTML = `NOTA: Se considerarán únicamente los cargos inherentes a la docencia y/o profesión. SIN REMUNERACIÓN`;
  }else{
    document.querySelector("#nota").innerHTML = ``;
  }
  //console.log(value);
  let categorias = seleccionesCargos.filter((e) => e.type == value);
  const select = document.getElementById("tipo_nombramiento");
  clearSelect(select);
  for (const option of categorias) {
    let newOption = document.createElement("option");
    newOption.value = option.value;
    newOption.text = option.name;
    select.add(newOption);
  }
  select.selectedIndex = 0;
  document.querySelector("#label_pts").innerHTML = "0";
  document.querySelector("#puntos").value = 0;

  




}

function validateTipoCargoEleccion(selectObject) {
  const value = selectObject.value;
  let valueObject = seleccionesCargos.find((e) => e.value == value);
  document.querySelector("#label_pts").innerHTML = valueObject.puntos;
  document.querySelector("#puntos").value = valueObject.puntos;
}

const eventosSelect = [
  {
    type: "2.1",
    typeName: "Evento Académico (Curso de 40 horas o más de duración)",
    value: "2.1.1",
    name: "Por evento",
    puntos: 4,
    min:40,
    max:1000
  },
  {
    type: "2.2",
    typeName: "Actividad con duración menor a 40 horas",
    value: "2.2.1",
    name: "Por cada actividad académica de 21 a 39 horas de duración",
    puntos: 3,
    min:21,
    max:39
  },
  {
    type: "2.2",
    typeName: "Actividad con duración menor a 40 horas",
    value: "2.2.2",
    name: "Por cada actividad académica de 9 a 20 horas de duración",
    puntos: 2,
    min:9,
    max:20
  },
  {
    type: "2.2",
    typeName: "Actividad con duración menor a 40 horas",
    value: "2.2.3",
    name: "Por cada actividad académica de 4 a 8 horas de duración",
    puntos: 1,
    min:4,
    max:8
  },
  {
    type: "2.2",
    typeName: "Actividad con duración menor a 40 horas",
    value: "2.2.4",
    name: "Por cada actividad o asistencia a charlas académicas menores a 4 horas",
    puntos: 0.5,
    min:0,
    max:3.99
  },
  {
    type: "2.2",
    typeName: "Actividad con duración menor a 40 horas",
    value: "2.2.5",
    name: "Por estudios de otros idiomas, por cada curso.",
    puntos: 1,
    min:0,
    max:40
  },
];

function validateEvent(selectObject) {
  const value = selectObject.value;
  //console.log(value);
  let categorias = eventosSelect.filter((e) => e.type == value);
  const select = document.getElementById("subcategoria");
  clearSelect(select);
  for (const option of categorias) {
    let newOption = document.createElement("option");
    newOption.value = option.value;
    newOption.text = option.name;
    select.add(newOption);
  }

  select.selectedIndex = 0;
  document.querySelector("#label_pts").innerHTML = "0";
  document.querySelector("#puntos").value = 0;
}

function validatePtsEvent(selectObject) {
  //console.log('Entra aqui')
  const value = selectObject.value;
  //console.log(value);
  let valueObject = eventosSelect.find((e) => e.value == value);
  console.log(valueObject);
  setMinAndMax(valueObject.min, valueObject.max);

  document.querySelector("#label_pts").innerHTML = valueObject.puntos;
  document.querySelector("#puntos").value = valueObject.puntos;
}

function validatePremios(selectObject) {
  //console.log("Validacion de formulario de premios");
  const value = selectObject.value;
  if (value == "5.1") {
    document.querySelector("#label_pts").innerHTML = "2";
    document.querySelector("#puntos").value = 2;
    document.querySelector("#nota").innerHTML = `NOTA: PREMIOS, PLAQUETAS O MENCIONES HONORÍFICAS: Se refiere a las distinciones académicas y profesionales a las que el profesor se ha hecho acreedor dentro de la Universidad y/o comunidad, dentro y afuera del país, por ejemplo: Distinciones, CUMLAUDE, Becas, Primer lugar en concursos académicos o de proyectos profesionales.`;
  } else if (value == "5.2") {
    document.querySelector("#label_pts").innerHTML = "1";
    document.querySelector("#puntos").value = 1;
    document.querySelector("#nota").innerHTML = `RECONOCIMIENTOS Y AGRADECIMIENTOS: Se refiere a las constancias o diplomas que reconocen la labor y servicios (más allá de sus obligaciones) dentro del ambiente educativo  y/o profesional.  Incluye reconocimientos por conferencia, charlas y/o cursos de implementación y/o actualización académica siempre y cuando no hayan sido contabilizados como Conferencias, Charlas y/cursos de implementación y/o actualización académica en la sección de INVESTIGACIONES Y/O PUBLICACIONES REALIZADAS`;
  }
}

function openModelPDF(url, hostname) {
  //console.log(hostname);
  //console.log("Open Modal ", url);
  $("#modalPdf").modal("show");
  $("#iframePDF").attr("src", `http://${hostname}/plataforma_farusac/${url}`);
}

function closeModal() {
  $("#modalPdf").modal("toggle");
}

function openModalAdm() {
  $("#modalSolicitudes").modal("show");
}

function closeModalAdm() {
  $("#modalSolicitudes").modal("toggle");
}

function openModalEditar() {
    let puntosActuales = document.querySelector("#puntos").value;
    document.getElementById("puntosActuales").value = puntosActuales;
    
    $('#modalEditarEstado').modal('show');
}

function closeModalEditar() {
    $('#modalEditarEstado').modal('hide');
    document.getElementById("formEditarEstado").reset();
}

const changeSelected = (e, id, value) => {
  const $select = document.querySelector(`#${id}`);
  $select.value = value;
};



const changeFile = () => {
  document.getElementById("archivo").style.display = "block";
  document.getElementById("viewFile").style.display = "none";
  document.getElementById("archivo").setAttribute("required", "");

}


const setMin = () => {
  let startDate = document.getElementById('startDate');
  let endDate = document.getElementById('endDate');
  //console.log(startDate.value);
  endDate.min = startDate.value;
  
}
const setMax = () => {
  let startDate = document.getElementById('startDate');
  let endDate = document.getElementById('endDate');
  //console.log(startDate.value);
  startDate.max = endDate.value;
  
}

const setMinAndMax = (min, max) =>{
  let horas = document.getElementById('duracion');
  horas.value = min;
  horas.min = min;
  horas.max = max;
}

const activateTxt = () =>{
  //console.log('Se activara...')
  document.getElementById('btn-nota').style.display = "none";
  document.getElementById('group-nota').style.display = "block";

}

 

