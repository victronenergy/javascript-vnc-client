#############################################################################
# Makefile - used by bitbake to prep website for ccgx
#############################################################################

####### Compiler, tools and options

TAR           = tar -cf
COMPRESS      = gzip -9f
COPY          = cp -f
SED           = sed
COPY_FILE     = $(COPY)
COPY_DIR      = $(COPY) -r
INSTALL_FILE  = install -m 644 -p
INSTALL_DIR   = $(COPY_DIR)
INSTALL_PROGRAM = install -m 755 -p
DEL_FILE      = rm -f
SYMLINK       = ln -f -s
DEL_DIR       = rmdir
DEL_FULL_DIR  = rm -rf
MOVE          = mv -f
CHK_DIR_EXISTS= test -d
MKDIR         = mkdir -p

# mkfile_path = $(abspath $(lastword $(MAKEFILE_LIST)))
# current_dir = $(notdir $(patsubst %/,%,$(dir $(mkfile_path))))
# builddir = $(patsubst %/,%,$(dir $(mkfile_path)))-build

# set DESTDIR to default value when not already set
DESTDIR ?= /var/www/javascript-vnc-client

####### Install
all:
#	./buildForCCGX.sh $(builddir)

# clean:
#	rm -rf $(builddir)

install:
	@$(CHK_DIR_EXISTS) $(DESTDIR) || $(MKDIR) $(DESTDIR)
	@$(CHK_DIR_EXISTS) $(DESTDIR)/include || $(MKDIR) $(DESTDIR)/include
	@$(CHK_DIR_EXISTS) $(DESTDIR)/styling || $(MKDIR) $(DESTDIR)/styling
	@$(CHK_DIR_EXISTS) $(DESTDIR)/scripts || $(MKDIR) $(DESTDIR)/scripts
	
	$(COPY_DIR) index.html $(DESTDIR)
	$(COPY_DIR) -R styling/* $(DESTDIR)/styling
	$(COPY_DIR) -R scripts/* $(DESTDIR)/scripts
	$(COPY_DIR) ext/noVNC/include/* $(DESTDIR)/include

	# Configure permissions
	chown -R www-data:www-data $(DESTDIR)*
