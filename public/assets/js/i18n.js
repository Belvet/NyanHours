(() => {
    const en = {
        'Mi panel':'Dashboard','Administración':'Administration','Usuarios':'Users','Clientes':'Clients','Reportes':'Reports','Salir':'Log out','OPERADOR':'OPERATOR',
        'Iniciar sesión':'Sign in','Ingresá con tu cuenta para registrar tus horas.':'Sign in to record your work hours.','Contraseña':'Password','Ingresar':'Sign in','Iniciá sesión para continuar.':'Sign in to continue.','El email o la contraseña no son correctos.':'The email or password is incorrect.','Ingresá un email y una contraseña válidos.':'Enter a valid email and password.',
        'Registrá y consultá tus horas trabajadas.':'Record and review your work hours.','Abrir planilla semanal':'Open weekly timesheet','Tiempo total registrado':'Total recorded time','Fecha':'Date','Cliente':'Client','Tiempo':'Time','Descripción':'Description','Todavía no registraste horas':'You have not recorded any hours yet','Completá tu primera planilla semanal para comenzar.':'Complete your first weekly timesheet to get started.','Abrir planilla':'Open timesheet','Tu cuenta tiene permisos de administrador.':'Your account has administrator permissions.','Consultá todo el equipo, los clientes y los totales acumulados.':'Review the entire team, clients, and accumulated totals.','Abrir panel administrativo':'Open administration','Ver resumen de horas':'View time summary',
        'Planilla semanal':'Weekly timesheet','Mi historial':'My history','No hay clientes activos':'There are no active clients','Un administrador debe crear un cliente antes de cargar horas.':'An administrator must create a client before recording hours.','Lun':'Mon','Mar':'Tue','Mié':'Wed','Jue':'Thu','Vie':'Fri','Sáb':'Sat','Dom':'Sun','Total':'Total','Inactivo':'Inactive','Podés escribir 1, 1.5 o 1:30':'You can enter 1, 1.5, or 1:30','Guardar semana':'Save week','Semana guardada correctamente.':'Week saved successfully.',
        'Registrá actividades individuales. También aparecen las horas cargadas desde la planilla.':'Record individual activities. Hours entered in the timesheet also appear here.','¿En qué trabajaste?':'What did you work on?','Seleccionar':'Select','Duración':'Duration','Agregar':'Add','No hay actividades':'There are no activities','Agregá una actividad o completá la planilla semanal.':'Add an activity or complete the weekly timesheet.','Sin actividad (cargado desde planilla)':'No activity (added from timesheet)','Eliminar':'Delete','Actividad agregada correctamente.':'Activity added successfully.','Actividad actualizada correctamente.':'Activity updated successfully.','Actividad eliminada correctamente.':'Activity deleted successfully.',
        'Panel administrativo':'Administration dashboard','Gestión':'Management','Administrá las cuentas del equipo y los clientes disponibles para registrar horas.':'Manage team accounts and clients available for time tracking.','Administrar usuarios':'Manage users','Administrar clientes':'Manage clients','Resumen de horas':'Time summary','Registros':'Entries','Mi planilla semanal':'My weekly timesheet','Volver al panel':'Back to dashboard',
        'Nuevo usuario':'New user','Administrá accesos, roles y tarifas.':'Manage access, roles, and rates.','Nombre':'Name','Rol':'Role','Tarifa/hora':'Hourly rate','Estado':'Status','Acciones':'Actions','Activo':'Active','Editar':'Edit','Desactivar':'Deactivate','Activar':'Activate','Crear usuario':'Create user','Tarifa por hora':'Hourly rate','Contraseña inicial':'Initial password','Mínimo 12 caracteres.':'At least 12 characters.','Cancelar':'Cancel','Editar usuario':'Edit user','Guardar cambios':'Save changes','Nueva contraseña':'New password','Dejala vacía para conservar la contraseña actual.':'Leave it empty to keep the current password.','Usuario creado correctamente.':'User created successfully.','Usuario actualizado correctamente.':'User updated successfully.','Usuario activado correctamente.':'User activated successfully.','Usuario desactivado correctamente.':'User deactivated successfully.',
        'Nuevo cliente':'New client','Definí qué clientes estarán disponibles al registrar horas.':'Choose which clients will be available for time tracking.','Creado':'Created','Todavía no hay clientes':'There are no clients yet','Creá el primero para comenzar a registrar horas.':'Create the first one to begin tracking time.','Crear cliente':'Create client','Nombre del cliente':'Client name','Color identificador':'Identification color','Se usará en toda la aplicación.':'It will be used throughout the application.','Editar cliente':'Edit client','El nuevo nombre también aparecerá en sus registros históricos.':'The new name will also appear in historical entries.','Cliente creado correctamente.':'Client created successfully.','Cliente actualizado correctamente.':'Client updated successfully.','Cliente activado correctamente.':'Client activated successfully.','Cliente desactivado correctamente.':'Client deactivated successfully.','Volver':'Back',
        'Desde':'From','Hasta':'To','Filtrar':'Filter','Limpiar':'Clear','Período seleccionado:':'Selected period:','Completá las dos fechas para aplicar el filtro.':'Complete both dates to apply the filter.','Ingresá un rango de fechas válido.':'Enter a valid date range.','La fecha desde no puede ser posterior a la fecha hasta.':'The start date cannot be later than the end date.','El período de Timesheet no puede superar los 31 días.':'The Timesheet period cannot exceed 31 days.','Período guardado correctamente.':'Period saved successfully.','Planilla de equipo':'Team timesheet','Revisá y editá las horas de todo el equipo por cliente.':'Review and edit the entire team’s hours by client.','Ver planilla':'View timesheet','Elegí un cliente':'Choose a client','Seleccioná el cliente para ver cuánto cargó cada integrante por día.':'Select a client to see how much each team member recorded per day.','Usuario':'User','Guardar planilla':'Save timesheet','Planilla del equipo guardada correctamente.':'Team timesheet saved successfully.','Los eventos detallados no pueden reducirse por debajo de su total en Time Tracker.':'Detailed events cannot be reduced below their Time Tracker total.',
        'Tiempo acumulado por persona y cliente.':'Accumulated time by person and client.','Total de todo el equipo':'Entire team total','Todavía no hay horas registradas':'No hours have been recorded yet','Los registros del equipo aparecerán aquí.':'Team entries will appear here.','Subtotal:':'Subtotal:','Sin actividad':'No activity','Editar actividad':'Edit activity','Actividad':'Activity','Guardar':'Save','Método no permitido.':'Method not allowed.'
    };
    const placeholders = {'Ej.: Diseño de newsletter':'E.g. Newsletter design','Seleccionar cliente':'Select client','Escribí la actividad…':'Enter the activity…'};
    const originalText = new WeakMap();
    const originalAttributes = new WeakMap();
    const dynamic = (text) => {
        if (en[text]) return en[text];
        if (text.startsWith('Hola, ')) return `Hello, ${text.slice(6)}`;
        if (text.startsWith('Horas de ')) return `Hours for ${text.slice(9)}`;
        if (text.startsWith('Sesión de ')) return `Signed in as ${text.slice(10)}`;
        return text;
    };
    const apply = (language) => {
        document.documentElement.lang = language;
        const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
        const nodes = []; while (walker.nextNode()) nodes.push(walker.currentNode);
        nodes.forEach((node) => {
            if (node.parentElement?.closest('script,style,[data-no-translate]')) return;
            if (!originalText.has(node)) originalText.set(node, node.nodeValue);
            const original = originalText.get(node), trimmed = original.trim();
            if (trimmed) node.nodeValue = original.replace(trimmed, language === 'en' ? dynamic(trimmed) : trimmed);
        });
        document.querySelectorAll('[placeholder],[title]').forEach((element) => {
            if (!originalAttributes.has(element)) originalAttributes.set(element,{placeholder:element.getAttribute('placeholder'),title:element.getAttribute('title')});
            const original = originalAttributes.get(element);
            if (original.placeholder !== null) element.setAttribute('placeholder',language==='en'?(placeholders[original.placeholder]||original.placeholder):original.placeholder);
            if (original.title !== null) element.setAttribute('title',language==='en'?(en[original.title]||original.title):original.title);
        });
        document.querySelectorAll('[data-language]').forEach((button)=>button.classList.toggle('active',button.dataset.language===language));
        localStorage.setItem('nyanhours_language',language);
    };
    document.addEventListener('click',(event)=>{const button=event.target.closest('[data-language]');if(button)apply(button.dataset.language);});
    apply(localStorage.getItem('nyanhours_language')==='en'?'en':'es');
})();
